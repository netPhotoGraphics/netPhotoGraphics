Nova - theme for netPhotoGraphics
=================================

A modern, responsive photo gallery theme without Bootstrap or other frameworks.
Plain CSS (Grid, variables, dark mode), album and photo grids, EXIF, map,
rating, favorites and lightbox.

Author:  Martin Kroul
Credits: Inspired by the Bootstrap theme by Vincent3569, whose functionality
         and template structure it follows.
         Developed in collaboration with Claude (AI assistant by Anthropic).
License: GNU General Public License, version 2 or later, like netPhotoGraphics.

Translations: see locale/README.txt

Photo map page (optional)
-------------------------
Theme options > "Photo map page" turns on /page/photomap: one map with all photos that have GPS coordinates,
plus a "Photo map" link in the menu (with a custom menu, add the custom page "photomap" yourself).
Built for big galleries (tested design target: 20 000+ geotagged photos):
 - the browser asks only for the visible map area, never for all points;
 - the server groups photos into ~60 px grid clusters (one SQL GROUP BY) and returns at most ~2000 markers,
   or the single points when fewer than 300 are in view;
 - clicking a single marker / a cluster that cannot be split further loads up to 9 thumbnails on demand;
 - only photos the visitor may see are counted (hidden, unpublished and password protected albums are left out).
Only Leaflet from the openStreetMap plugin files is used; the plugin itself does not have to be enabled.
Map tiles come from tile.openstreetmap.org (light use; for heavy traffic switch to your own tile provider in js/nova-map.js).
