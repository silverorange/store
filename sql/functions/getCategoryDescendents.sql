/**
 * Finds all descendant categories in the category subtree rooted at the given
 * category. If the given category is null, finds all descendant categories
 * for all categories.
 *
 * Results are returned in (category, descendant) pairs where the descendant
 * is the category id of a category either directly or indirectly descended
 * from the category id in the category field. For convenience, all categories
 * are considered descendants of themselves except for the root category. This
 * means (category, descentent) pairs such as (1, 1) may exist but a
 * (category, descendant) pair of (null, null) will never exist.
 *
 * @param_id INTEGER: the category id to get descendants for. Specify null to
 *                    get descendants for all categories.
 *
 * Returns a type_category_descendants pair containing the category id of all
 * categories in the subtree.
 */
CREATE TYPE type_category_descendants AS (category INTEGER, descendant INTEGER);
CREATE OR REPLACE FUNCTION getCategoryDescendants (INTEGER) RETURNS SETOF type_category_descendants AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_row RECORD;
		local_row2 RECORD;
		return_row type_category_descendants%ROWTYPE;
	BEGIN
		IF param_id IS NULL THEN
			FOR local_row IN SELECT parent as category, id as descendant FROM Category WHERE parent IS NULL LOOP
				FOR local_row2 IN SELECT category, descendant FROM getCategoryDescendants(local_row.descendant) LOOP
					SELECT INTO return_row local_row.category, local_row2.descendant;
					RETURN NEXT return_row;
					SELECT INTO return_row local_row2.category, local_row2.descendant;
					RETURN NEXT return_row;
				END LOOP;
			END LOOP;
		ELSE
			SELECT INTO return_row param_id, param_id;
			RETURN NEXT return_row;	
			FOR local_row IN SELECT parent as category, id as descendant FROM Category WHERE parent = param_id LOOP
				FOR local_row2 IN SELECT category, descendant FROM getCategoryDescendants(local_row.descendant) LOOP
					SELECT INTO return_row local_row.category, local_row2.descendant;
					RETURN NEXT return_row;
					SELECT INTO return_row local_row2.category, local_row2.descendant;
					RETURN NEXT return_row;
				END LOOP;
			END LOOP;
		END IF;

		RETURN NEXT return_row;
		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
