# TTN Mapper

This repository contains the current production TTN Mapper system's source code.

## Directories

* `integrations` - Contains API endpoints to where TTN or other services send data.
* `maintenance` - Scripts use for maintenance of the system. Mostly import, export and cleanup of data.
* `processing_scripts` - Scripts that does periodic processing of the data. The main business logic of creation of the maps resides here.
* `tms` - A tile map server written in PHP that serves out PNG tiles with data.
* `web` - The web pages for the website.

