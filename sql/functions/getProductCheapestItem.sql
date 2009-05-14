/*
 * Gets the cheapest item for a product
 *
 * @param_product_id INTEGER: the product id.
 * @param_region_id INTEGER:  the pricing region id id.
 *
 * @return INTEGER: the id of the cheapest item for the product. If no item
 *                  exists, NULL is returned.
 */
CREATE OR REPLACE FUNCTION getProductCheapestItem(INTEGER, INTEGER) RETURNS INTEGER AS $$
	DECLARE
		param_product_id ALIAS FOR $1;
		param_region_id  ALIAS FOR $2;
		local_item_id    INTEGER;
		local_item_row   RECORD;
	BEGIN
		local_item_id = NULL;

		FOR local_item_row IN SELECT
			Item.id
		FROM Item
			LEFT OUTER JOIN ItemRegionBinding ON ItemRegionBinding.item = Item.id
		WHERE region = param_region_id AND product = param_product_id
		ORDER BY price, sale_discount ASC
		LIMIT 1
		LOOP
			local_item_id = local_item_row.id;
		END LOOP;

		RETURN local_item_id;
	END;
$$ LANGUAGE 'plpgsql';

