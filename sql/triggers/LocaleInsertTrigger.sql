CREATE OR REPLACE FUNCTION createAdLocaleBindings () RETURNS trigger AS $$ 
    BEGIN
		INSERT INTO AdLocaleBinding (ad, locale)
			SELECT Ad.id as ad, NEW.id as locale;

        RETURN NULL;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER LocaleInsertTrigger AFTER INSERT ON Locale 
    FOR EACH ROW EXECUTE PROCEDURE createAdLocaleBindings();
