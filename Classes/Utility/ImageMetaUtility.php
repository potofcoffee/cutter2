<?php
/*
 * cutter2
 *
 * Copyright (c) 2018 Christoph Fischer, https://christoph-fischer.org
 * Author: Christoph Fischer, chris@toph.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace VMFDS\Cutter\Utility;

class ImageMetaUtility
{

    public static function getImageMetaData($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        list(, , $sourceImageType) = getimagesize($file);

        /*
         * EXIF contains a bunch of data we'll probably never need formatted in ways
         * that are difficult to use. We'll normalize it and just extract the fields
         * that are likely to be useful. Fractions and numbers are converted to
         * floats, dates to unix timestamps, and everything else to strings.
         */
        $meta = array(
            'aperture' => 0,
            'credit' => '',
            'camera' => '',
            'caption' => '',
            'created_timestamp' => 0,
            'copyright' => '',
            'focal_length' => 0,
            'iso' => 0,
            'shutter_speed' => 0,
            'title' => '',
            'orientation' => 0,
            'keywords' => array(),
        );

        $iptc = array();
        /*
         * Read IPTC first, since it might contain data not available in exif such
         * as caption, description etc.
         */
        if (is_callable('iptcparse')) {
            getimagesize($file, $info);

            if (!empty($info['APP13'])) {
                $iptc = iptcparse($info['APP13']);

                // Headline, "A brief synopsis of the caption."
                if (!empty($iptc['2#105'][0])) {
                    $meta['title'] = trim($iptc['2#105'][0]);
                    /*
                     * Title, "Many use the Title field to store the filename of the image,
                     * though the field may be used in many ways."
                     */
                } elseif (!empty($iptc['2#005'][0])) {
                    $meta['title'] = trim($iptc['2#005'][0]);
                }

                if (!empty($iptc['2#120'][0])) { // description / legacy caption
                    $caption = trim($iptc['2#120'][0]);

                    StringUtility::mbstring_binary_safe_encoding();
                    $caption_length = strlen($caption);
                    StringUtility::reset_mbstring_encoding();

                    if (empty($meta['title']) && $caption_length < 80) {
                        // Assume the title is stored in 2:120 if it's short.
                        $meta['title'] = $caption;
                    }

                    $meta['caption'] = $caption;
                }

                if (!empty($iptc['2#110'][0])) // credit
                {
                    $meta['credit'] = trim($iptc['2#110'][0]);
                } elseif (!empty($iptc['2#080'][0])) // creator / legacy byline
                {
                    $meta['credit'] = trim($iptc['2#080'][0]);
                }

                if (!empty($iptc['2#055'][0]) && !empty($iptc['2#060'][0])) // created date and time
                {
                    $meta['created_timestamp'] = strtotime($iptc['2#055'][0] . ' ' . $iptc['2#060'][0]);
                }

                if (!empty($iptc['2#116'][0])) // copyright
                {
                    $meta['copyright'] = trim($iptc['2#116'][0]);
                }

                if (!empty($iptc['2#025'][0])) { // keywords array
                    $meta['keywords'] = array_values($iptc['2#025']);
                }
            }
        }

        /**
         * Filters the image types to check for exif data.
         *
         * @since 2.5.0
         *
         * @param array $image_types Image types to check for exif data.
         */
        if (is_callable('exif_read_data') && in_array($sourceImageType,
                array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM))) {
            $exif = @exif_read_data($file);

            if (!empty($exif['ImageDescription'])) {
                StringUtility::mbstring_binary_safe_encoding();
                $description_length = strlen($exif['ImageDescription']);
                StringUtility::reset_mbstring_encoding();

                if (empty($meta['title']) && $description_length < 80) {
                    // Assume the title is stored in ImageDescription
                    $meta['title'] = trim($exif['ImageDescription']);
                }

                if (empty($meta['caption']) && !empty($exif['COMPUTED']['UserComment'])) {
                    $meta['caption'] = trim($exif['COMPUTED']['UserComment']);
                }

                if (empty($meta['caption'])) {
                    $meta['caption'] = trim($exif['ImageDescription']);
                }
            } elseif (empty($meta['caption']) && !empty($exif['Comments'])) {
                $meta['caption'] = trim($exif['Comments']);
            }

            if (empty($meta['credit'])) {
                if (!empty($exif['Artist'])) {
                    $meta['credit'] = trim($exif['Artist']);
                } elseif (!empty($exif['Author'])) {
                    $meta['credit'] = trim($exif['Author']);
                }
            }

            if (empty($meta['copyright']) && !empty($exif['Copyright'])) {
                $meta['copyright'] = trim($exif['Copyright']);
            }
            if (!empty($exif['FNumber'])) {
                $meta['aperture'] = round(self::exifFrac2Dec($exif['FNumber']), 2);
            }
            if (!empty($exif['Model'])) {
                $meta['camera'] = trim($exif['Model']);
            }
            if (empty($meta['created_timestamp']) && !empty($exif['DateTimeDigitized'])) {
                $meta['created_timestamp'] = self::exifDate2Ts($exif['DateTimeDigitized']);
            }
            if (!empty($exif['FocalLength'])) {
                $meta['focal_length'] = (string)self::exifFrac2Dec($exif['FocalLength']);
            }
            if (!empty($exif['ISOSpeedRatings'])) {
                $meta['iso'] = is_array($exif['ISOSpeedRatings']) ? reset($exif['ISOSpeedRatings']) : $exif['ISOSpeedRatings'];
                $meta['iso'] = trim($meta['iso']);
            }
            if (!empty($exif['ExposureTime'])) {
                $meta['shutter_speed'] = (string)self::exifFrac2Dec($exif['ExposureTime']);
            }
            if (!empty($exif['Orientation'])) {
                $meta['orientation'] = $exif['Orientation'];
            }
        }

        foreach (array('title', 'caption', 'credit', 'copyright', 'camera', 'iso') as $key) {
            if ($meta[$key] && !StringUtility::seems_utf8($meta[$key])) {
                $meta[$key] = utf8_encode($meta[$key]);
            }
        }

        foreach ($meta['keywords'] as $key => $keyword) {
            if (!StringUtility::seems_utf8($keyword)) {
                $meta['keywords'][$key] = utf8_encode($keyword);
            }
        }

        //$meta = wp_kses_post_deep($meta);

        return $meta;
    }


    protected static function exifFrac2Dec($str) {
        @list( $n, $d ) = explode( '/', $str );
        if ( !empty($d) )
            return $n / $d;
        return $str;
    }

    protected static function exifDate2Ts($str) {
        @list( $date, $time ) = explode( ' ', trim($str) );
        @list( $y, $m, $d ) = explode( ':', $date );

        return strtotime( "{$y}-{$m}-{$d} {$time}" );
    }
}