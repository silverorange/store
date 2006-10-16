##
## i18n Makefile
##

DOMAIN = store
XGETTEXT = xgettext --from-code=UTF-8
MSGFMT = msgfmt -c
MSGMERGE = msgmerge --no-fuzzy-matching
MSGUNIQ = msguniq
INSTALL = install
INSTALLDIR = mkdir -p
DATA_MODE = 660

TRANSLATIONS := en_CA en_GB
MO_FILES := $(patsubst %,%.mo, $(TRANSLATIONS))

POTFILE = $(DOMAIN).pot

TARGETS = $(MO_FILES)

LOCALEDIR = ../locale

######################################

all:
	make $(TARGETS)

potfiles-php:
	rm -f $@-t $@
	(sed -e '/^#/d' < potfiles.in) > $@-t
	(for i in `cat $@-t`; do find $$i -name *.php; done) >> $@
	rm -f $@-t

potfiles-xml:
	rm -f $@-t $@
	(sed -e '/^#/d' < potfiles.in) > $@-t
	(for i in `cat $@-t`; do find $$i -name *.xml; done) >> $@
	rm -f $@-t

pot: $(POTFILE)

$(POTFILE): potfiles-php potfiles-xml
	$(XGETTEXT) -o $(POTFILE) -L Php -f potfiles-php
	$(XGETTEXT) -j -o $(POTFILE) -L Glade -f potfiles-xml
	$(MSGUNIQ) -o $(POTFILE) $(POTFILE)

%.mo: %.po
	$(MSGFMT) -o $@ $<

install:
	for i in $(TRANSLATIONS); do \
		$(INSTALLDIR) $(LOCALEDIR)/$$i/LC_MESSAGES ; \
                $(INSTALL) -m $(DATA_MODE) $$i.mo $(LOCALEDIR)/$$i/LC_MESSAGES/$(DOMAIN).mo ; \
        done

update: pot
	for i in $(TRANSLATIONS); do \
		$(MSGMERGE) -U $$i.po $(POTFILE); \
	done

clean:
	rm -f potfiles-* *.mo *.pot
