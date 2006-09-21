/**
 * Finds all descendent categories in the category subtree rooted at the given
 * category. If the given category is null, finds all descendent categories
 * for all categories.
 *
 * Results are returned in (category, descendent) pairs where the descendent
 * is the category id of a category either directly or indirectly descended
 * from the category id in the category field. For convenience, all categories
 * are considered descendents of themselves except for the root category. This
 * means (category, descentent) pairs such as (1, 1) may exist but a
 * (category, descendent) pair of (null, null) will never exist.
 *
 * @param_id INTEGER: the category id to get descendents for. Specify null to
 *                    get descendents for all categories.
 *
 * Returns a type_category_descendents pair containing the category id of all
 * categories in the subtree.
 */
CREATE TYPE type_category_descendents AS (category INTEGER, descendent INTEGER);
CREATE OR REPLACE FUNCTION getCategoryDescendents (INTEGER) RETURNS SETOF type_category_descendents AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_row RECORD;
		local_row2 RECORD;
		return_row type_category_descendents%ROWTYPE;
	BEGIN
		IF param_id IS NULL THEN
			FOR local_row IN SELECT parent as category, id as descendent FROM Category WHERE parent IS NULL LOOP
				FOR local_row2 IN SELECT category, descendent FROM getCategoryDescendents(local_row.descendent) LOOP
					SELECT INTO return_row local_row.category, local_row2.descendent;
					RETURN NEXT return_row;
					SELECT INTO return_row local_row2.category, local_row2.descendent;
					RETURN NEXT return_row;
				END LOOP;
			END LOOP;
		ELSE
			SELECT INTO return_row param_id, param_id;
			RETURN NEXT return_row;	
			FOR local_row IN SELECT parent as category, id as descendent FROM Category WHERE parent = param_id LOOP
				FOR local_row2 IN SELECT category, descendent FROM getCategoryDescendents(local_row.descendent) LOOP
					SELECT INTO return_row local_row.category, local_row2.descendent;
					RETURN NEXT return_row;
					SELECT INTO return_row local_row2.category, local_row2.descendent;
					RETURN NEXT return_row;
				END LOOP;
			END LOOP;
		END IF;

		RETURN NEXT return_row;
		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
