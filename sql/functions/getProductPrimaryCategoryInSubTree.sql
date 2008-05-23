/**
 * Similar to ProductPrimaryCategoryView, but operates on a subtree of
 * categories.
 *
 * @param_id INTEGER: the category id of the sub-tree.
 *
 * Returns a type_product_primary_category pair containing the product id and
 * the primary category id.
 */
CREATE TYPE type_product_primary_category AS (product INTEGER, primary_category INTEGER);
CREATE OR REPLACE FUNCTION getProductPrimaryCategoryInSubTree (INTEGER) RETURNS SETOF type_product_primary_category AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_row RECORD;
		return_row type_category_descendants%ROWTYPE;
	BEGIN
		FOR local_row IN
			SELECT categoryproductbinding.product, min(categoryproductbinding.category) AS primary_category
			FROM categoryproductbinding where category in (select descendant from getCategoryDescendants(param_id))
			GROUP BY categoryproductbinding.product LOOP

			SELECT INTO return_row local_row.product, local_row.primary_category;
			RETURN NEXT return_row;
		END LOOP;
		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
