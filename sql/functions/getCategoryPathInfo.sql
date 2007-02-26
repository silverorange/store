/*
 * Returns path information for a category.
 *
 * @param_id INTEGER: the id of the category.
 *
 * @returned_row type_category_path_info: a row containing id, parent, shortname, and title.
 *
 * Returns a set of type_category_path_info. The set is ordered from the leaf category to the root category.
 * This function does not check if the parent categories are enabled.
 * If the category is not found, an empty record set is returned.
 */
CREATE TYPE type_category_path_info AS (id INTEGER, parent INTEGER, shortname VARCHAR(255), title VARCHAR(255));

CREATE OR REPLACE FUNCTION getCategoryPathInfo(INTEGER) RETURNS SETOF type_category_path_info AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_id INTEGER;
		returned_row type_category_path_info%ROWTYPE;
	BEGIN
		local_id := param_id;
		WHILE local_id is not null LOOP
			BEGIN
				SELECT INTO returned_row id, parent, shortname, title
				FROM Category
				WHERE id = local_id;

				-- return the row
				IF FOUND THEN
					RETURN NEXT returned_row;
				END IF;

				-- move up the tree
				local_id := returned_row.parent;
			END;
		END LOOP;
		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
