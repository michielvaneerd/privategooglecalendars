#!/usr/bin/env bash

# Create ZIP file that's ready for releasing to Wordpress Plugin repository.

zip -r pgc.zip . -x "**/.DS_Store" -x ".DS_Store" -x ".git*" -x "docker-compose.yml" -x "docs/*" -x "node_modules/*" -x "lib/node_modules/*" -x "package-lock.json" -x "package.json" -x "lib/package-lock.json" -x "lib/package.json" -x "utils/*" -x "src/*" -x "lib/src/*" -x "lib/yarn.lock" -x "lib/webpack.config.js"

