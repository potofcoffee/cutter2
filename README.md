# cutter
Versatile image cropper tool

This is an online tool to cut images with predefined aspect ratios and label them with a copyrights watermark.
For further processing, images can be ...
- simply saved to a server directory
- downloaded
- assigned to a kOOL event in various fields
- assigned to a vmfds_sermons sermon record in a TYPO3 database.

CUTTER interfaces with kOOL (http://www.churchtool.org) and TYPO3 (via the vmfds_sermons extension).
It provides ...
- event selection (for assigning a picture to an event)
- sermon selection (for assigning a picture to a sermon)

## Development

### Factories
CUTTER can be extended in several areas, where factory methods are used to return one of several possible classes:
- Providers: import from an online source
- Converters: convert an image format
- Templates: configuration sets for handling an image (aspect ration, legal text, default target, ...)
- Writers: write an image to an image file in a specific format
- Processors: pass an image file to the intended target

### Coding standards
CUTTER coding will conform to PSR/2 standards.

### Namespaces
All CUTTER classes will be namespaced under the \VMFDS\Cutter namespace.
