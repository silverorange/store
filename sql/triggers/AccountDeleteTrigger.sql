CREATE OR REPLACE FUNCTION deleteAccount () RETURNS trigger AS $$
    BEGIN
		delete from NewsletterSubscriber where email = OLD.email;
        RETURN OLD;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER AccountDeleteTrigger BEFORE DELETE ON Account
    FOR EACH ROW EXECUTE PROCEDURE deleteAccount();
