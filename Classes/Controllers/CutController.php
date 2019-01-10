<?php
/*
 * CUTTER
 * Versatile Image Cutter and Processor
 * http://github.com/VolksmissionFreudenstadt/cutter
 *
 * Copyright (c) 2015 Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
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

namespace VMFDS\Cutter\Controllers;

use VMFDS\Cutter\Core\Debugger;
use VMFDS\Cutter\Core\ImageOverlay;

/**
 * Description of CutController
 *
 * @author chris
 */
class CutController extends AbstractController
{
    protected $data = array();

    /**
     * Override default renderView() function
     *
     * This controller will behave as a JSON controller, which means:
     * (1) default view output will NOT happen
     * (2) Content-Type will be set to application/json
     * (3) an internal array $data will be output as JSON
     */
    public function renderView()
    {
        $this->view->setContentType('application/json');
        $this->view->sendContentTypeHeader();
        echo json_encode($this->data);
    }

    /**
     * Cut the image
     * @action cut
     */
    function doAction()
    {
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('do Action');
        $session = \VMFDS\Cutter\Core\Session::getInstance();
        $request = \VMFDS\Cutter\Core\Request::getInstance();

        // we just die, since this is a headless controller
        if (!$session->hasArgument('workFile')) die('workfile');
	    $request->requireArguments(array('x', 'y', 'w', 'h', 'category', 'set', 'color'));
	    \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('All necessary arguments are present.');

	    // update meta from request
	    $session->setArgument('meta', array_replace_recursive($session->getArgument('meta'), $request->getArgument('meta')));

        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Loading template '.$request->getArgument('category').'/'.$request->getArgument('set'));
        $template  = \VMFDS\Cutter\Factories\TemplateFactory::get($request->getArgument('category'), $request->getArgument('set'));
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Loading processor '.$template->getProcessor());
        $processor = $template->getProcessorObject();
        $processor->setOptionsArray($template->getProcessorOptions());

        $meta    = $session->getArgument('meta');

        $destinationFile = CUTTER_basePath.'Temp/Processed/'.
            pathinfo($session->getArgument('workFile'), PATHINFO_FILENAME)
            .'_'.$template->getSuffix().'.jpg';

        // import image from a converter
        $imageFile = CUTTER_uploadPath.$session->getArgument('workFile');
        $converter = \VMFDS\Cutter\Factories\ConverterFactory::getFileHandler($imageFile);

        $colorString = $request->getArgument('color');
        $color       = new \VMFDS\Cutter\Core\Color($colorString);
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Color hex string: '.$request->getArgument('color'));

        // process image
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Start image processing.');
        $image = new \VMFDS\Cutter\Core\Image($converter->getImage($imageFile));
        $image->resize($request->getArgument('x'), $request->getArgument('y'),
            $template->getWidth(), $template->getHeight(),
            $request->getArgument('w'), $request->getArgument('h'));
        if ($request->hasArgument('legal')) {
            $legal = $request->getArgument('legal').($meta['license']['short'] ? ' // Lizenz: '.$meta['license']['short'] : '');
            \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Legal text is "'.$legal.'"');
            $image->setLegalText('Bild: '.$legal, $template->getWidth(),
                $template->getHeight(), $color);
        } else $legal='';



        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug(
            'Creating JPEG image...');
        $image->toJpeg($destinationFile, 100);


        // add overlay text, if any
        if ($request->hasArgument('overlayText') && ($request->getArgument('overlayText') != '')) {
            \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Adding overlay text...');
            $overlayImg = new ImageOverlay($template->getWidth(), $template->getHeight());
            $overlayImg->compositeImageFile($destinationFile, \Imagick::COMPOSITE_DEFAULT, 0, 0);

            $overlayOffset = $overlayImg->getTextMetrics(
                ' ',
                $request->getArgument('overlayFontFile'),
                $request->getArgument('overlayFontSize')
            )['textWidth'];

            $overlayImg->textBlock(
                $request->getArgument('overlayText'),
                $request->getArgument('overlayFontFile'),
                $request->getArgument('overlayFontSize'),
                $colorString = $request->getArgument('overlayColor'),
                $overlayOffset,
                -$overlayOffset,
                $template->getWidth()-(2*$overlayOffset),
                $request->getArgument('overlayAlignment')
            );
            $overlayImg->writeImage($destinationFile);
        }



        // Embed IPTC data
        $session = \VMFDS\Cutter\Core\Session::getInstance();
        $i       = new \VMFDS\Cutter\Core\IPTC($destinationFile);
        $i->set(IPTC_BYLINE, $meta['author']);
        $i->set(IPTC_COPYRIGHT_STRING, $legal.', '.$meta['url']);
        $i->set(IPTC_ORIGINATING_PROGRAM, CUTTER_software);
        $i->set(IPTC_PROGRAM_VERSION, CUTTER_version);
        $i->set(IPTC_SOURCE, $session->getArgument('original_url'));
        $i->set(IPTC_REFERENCE_NUMBER, $session->getArgument($meta['id']));
        if (is_array($meta)) {
            $i->set(IPTC_SPECIAL_INSTRUCTIONS, json_encode($meta));
        }
        $i->set(IPTC_CAPTION, $meta['title']);
        $i->set(IPTC_HEADLINE, $meta['title']);
        $i->set(IPTC_LOCAL_CAPTION, $meta['title']);
        $i->set(IPTC_KEYWORDS, (is_array($meta['keywords']) ? join(', ', $meta['keywords']) : $meta['keywords']));
        $i->set(IPTC_SOURCE, $meta['url']);
        $i->write();

        // Set EXIF comment
        $commentFile = CUTTER_basePath.'Temp/'.pathinfo($session->getArgument('workFile'),
                PATHINFO_FILENAME)
            .'_'.$template->getSuffix().'.txt';
        $fp          = fopen($commentFile, 'w');
        fwrite($fp,
            'Original url: '.$meta['url']."\r\n"
            .'Copyright: '.$legal."\r\n"
            .'Downloaded: '.strftime('%d.%m.%Y, %H:%M:%S')."\r\n"
            .'Cut template: '.$template->getKey().' ('.$template->getWidth().'x'.$template->getHeight().")\r\n"
            .'Cut area: '.$request->getArgument('x').', '.$request->getArgument('x').', '.$request->getArgument('w').', '.$request->getArgument('h')."\r\n"
            .'Text color: #'.$request->getArgument('color')."\r\n"
            .'CUTTER '.CUTTER_version);
        fclose($fp);
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Adding EXIF comment');
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('External command: '.'jhead -ci '.$commentFile.' '.$destinationFile);
        exec('jhead -ci '.$commentFile.' '.$destinationFile);
        unlink($commentFile);

        // Processor: What do we do with the finished file?
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug(
            'Calling final file processor...');
        $this->data = $this->callProcessor($processor, $destinationFile);
    }

    /**
     * Call a processor on an image file
     * Recursively fall back to a possible fallback processor
     * @param \VMFDS\Cutter\Processors\AbstractProcessor $processor Processor object
     * @param \string $file Path to file
     * @return array Results array
     */
    private function callProcessor($processor, $file)
    {
        $request    = \VMFDS\Cutter\Core\Request::getInstance();
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Calling file processor '.print_r($processor,
                1));
        $results    = $processor->process($file,
            $request->getArgumentsArray($processor->requiresArguments()));
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Processor results '.print_r($results,
                1));
        $this->data = $results;

        // Fallback to another processor?
        if ($results['result'] == $processor::RESULT_FALLBACK) {
            $fallbackProcessor = \VMFDS\Cutter\Factories\ProcessorFactory::get('Download');
            $results           = $this->callProcessor($fallbackProcessor, $file);
        }
        return $results;
    }
}