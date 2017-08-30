#!/bin/sh

ZIP_FILE_NAME=shopgate-opencart-module.ocmod.zip

rm -rf src/shopgate/vendor release/package $ZIP_FILE_NAME
mkdir release/package
composer install -vvv --no-dev
rsync -av --exclude-from './release/exclude-filelist.txt' ./src/ release/package/upload
rsync -av ./modman release/package/upload
rsync -av ./README.md release/package/upload/shopgate
rsync -av ./LICENSE.md release/package/upload/shopgate
rsync -av ./CONTRIBUTING.md release/package/upload/shopgate
rsync -av ./CHANGELOG.md release/package/upload/shopgate
cd release/package
zip -r ../../$ZIP_FILE_NAME .
