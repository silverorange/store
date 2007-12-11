/*
 * Gets price range information for products in a particular region.
 *
 * @param_region INTEGER: the region for which to get product price range information.
 *
 * @returned_row type_product_price_range: a row containing product, region,
 * 	min_price, max_price, and the number of items (num_items)
 */
CREATE TYPE type_product_price_range AS (product INTEGER, region INTEGER,
	min_price DECIMAL(9, 2), max_price DECIMAL(9, 2), num_items INTEGER);

CREATE OR REPLACE FUNCTION getProductPriceRange(INTEGER, BOOLEAN) RETURNS SETOF type_product_price_range AS $$
	DECLARE
		param_region ALIAS FOR $1;
		param_original_price ALIAS FOR $2;
		returned_row type_product_price_range%ROWTYPE;
	BEGIN
	FOR returned_row IN
		SELECT Product.id AS product, param_region AS region,

			case when param_original_price = true then
				min(price)
				else min(price - (price * coalesce(SaleDiscount.discount_percentage, 0)))
			end	AS min_price,

			case when param_original_price = true then
				max(price)
				else max(price - (price * coalesce(SaleDiscount.discount_percentage, 0)))
			end	AS max_price,

			count(Item.id) AS num_items

			FROM ItemRegionBinding
			inner join Item ON ItemRegionBinding.item = Item.id AND ItemRegionBinding.region = param_region
			inner join Product ON Item.product = Product.id
			left outer join SaleDiscount on SaleDiscount.id = Item.sale_discount
		GROUP BY product.id

	LOOP
		RETURN NEXT returned_row;
	END LOOP;
		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
