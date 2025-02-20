#!/bin/bash

wp theme activate wporg-planet-wp

wp rewrite structure '/%year%/%monthnum%/%postname%/'
wp rewrite flush --hard

wp option update blogname "Planet WordPress"
wp option update blogdescription "The latest news from the WordPress community"
