CREATE OR REPLACE FUNCTION deleteOrder () RETURNS trigger AS $$ 
    BEGIN
		update AdLocaleBinding set
			total = total - OLD.item_total,
			total_orders = total_orders - 1
		where locale = OLD.locale AND ad = OLD.ad;

        RETURN OLD;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER OrderDeleteTrigger BEFORE DELETE ON Orders
    FOR EACH ROW EXECUTE PROCEDURE deleteOrder();
