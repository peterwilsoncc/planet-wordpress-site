#!/bin/bash

wp theme activate wporg-planet-wp

wp rewrite structure '/%year%/%monthnum%/%postname%/'
wp rewrite flush
