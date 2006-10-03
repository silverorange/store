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

potfiles:
	rm -f $@-t $@
	(sed -e '/^#/d' < $@.in) > $@-t
	(for i in `cat $@-t`; do find $$i -name *.php | grep -v \.sync; done) >> $@
	rm -f $@-t

pot: $(POTFILE)

$(POTFILE): potfiles
	$(XGETTEXT) -k_P -o $(POTFILE) -f potfiles
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
	rm -f potfiles *.mo *.pot
