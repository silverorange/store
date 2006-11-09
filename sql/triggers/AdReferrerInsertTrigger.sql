CREATE OR REPLACE FUNCTION insertAdReferrer () RETURNS trigger AS $$ 
    BEGIN
		UPDATE Ad SET total_referrers = total_referrers + 1 where Ad.id = NEW.ad;
        RETURN null;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER AdReferrerInsertTrigger AFTER insert ON AdReferrer
    FOR EACH ROW EXECUTE PROCEDURE insertAdReferrer();
