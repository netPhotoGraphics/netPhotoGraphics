Nova translations
=================
netPhotoGraphics (2.00.07+) has no separate theme translations. Theme strings use plain gettext()
and are translated through the core language file (npgCore/locale/<lang>/LC_MESSAGES/core.po/.mo),
or through plugins/locale/<lang>/LC_MESSAGES/ which takes over as soon as that folder exists
and survives netPhotoGraphics updates.

How to add the Nova strings:
 - Poedit: put the theme into the "themes" folder that your catalog scans and use
   "Catalog > Update from source code", or
 - Poedit: "Catalog > Update from POT file" with nova.pot (new language) / merge nova-cs_CZ.po (Czech).

nova.pot      - template with all strings specific to Nova
nova-cs_CZ.po - Czech translation of those strings
