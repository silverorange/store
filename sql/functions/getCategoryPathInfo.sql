/*
 * Returns path information for a category.
 *
 * @param_parent INTEGER: the id of the category.
 *
 * @returned_row type_category_path_info: a row containing id, parent, shortname, title
 *
 * Returns a set of returned_rows ordered from leaf to root category.
 * Checking if the parent categories are enabled is left up to sp_category_find.
 * If the category is not found, returns an empty recordset
 */
CREATE TYPE type_category_path_info AS (id INTEGER, parent INTEGER, shortname VARCHAR(255), title VARCHAR(255));

CREATE OR REPLACE FUNCTION getCategoryPathInfo(INTEGER) RETURNS SETOF type_category_path_info AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_id INTEGER;
		local_count INTEGER;
		returned_row type_category_path_info%ROWTYPE;
	BEGIN
		local_id := param_id;

		WHILE local_id is not null LOOP
			BEGIN
				SELECT INTO local_count count(id) FROM Category WHERE parent = local_id;

				IF local_count != 0 THEN

					SELECT INTO local_count count(id) FROM Category WHERE parent IN (SELECT id FROM Category WHERE parent = local_id);

					IF local_count = 0 THEN
						SELECT INTO local_count count(product) FROM CategoryProductBinding WHERE category IN (SELECT id FROM Category WHERE parent = local_id);
					END IF;
				END IF;

				-- Get the results
				SELECT INTO returned_row id, parent, shortname, title
				FROM Category
				WHERE id = local_id;

				-- Return the navbar results
				IF FOUND THEN
					RETURN NEXT returned_row;
				END IF;

				-- Get next parent id.
				local_id := returned_row.parent;

			END;
		END LOOP;

		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
