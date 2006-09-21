CREATE OR REPLACE FUNCTION updateOrder () RETURNS trigger AS $$ 
    BEGIN
		IF (OLD.ad = NEW.ad AND OLD.item_total = NEW.item_total AND OLD.locale = NEW.locale) THEN
			RETURN null;
		END IF;

		UPDATE AdLocaleBinding SET total = total - OLD.item_total, total_orders = total_orders - 1
			where ad = OLD.ad and locale = OLD.locale;

		PERFORM ad from AdLocaleBinding where ad = NEW.ad and locale = NEW.locale LIMIT 1;

		IF FOUND THEN
			update AdLocaleBinding set
				total = AdLocaleBinding.total + NEW.item_total,
				total_orders = total_orders + 1
			where locale = NEW.locale AND ad = NEW.ad;
		ELSE
			insert into AdLocaleBinding (ad, locale, total, total_orders)
			select Ad.id, NEW.locale, NEW.item_total, 1 from Ad where Ad.id = NEW.ad;
		END IF;

        RETURN null;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER OrderUpdateTrigger AFTER update ON Orders
    FOR EACH ROW EXECUTE PROCEDURE updateOrder();
