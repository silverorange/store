/*
 * Returns path information for a category.
 *
 * @param_id INTEGER: the id of the category.
 * @param_twig_threshold INTEGER: the maximum number of products in the subtree for the category to be considered a twig.
 *
 * @returned_row type_category_path_info: a row containing id, parent, shortname, title, and twig.
 *
 * Returns a set of type_category_path_info. The set is ordered from the leaf category to the root category.
 * If the category is not found, an empty record set is returned.
 * A catgeory is a twig category if the total number of products in the subtree below it is below the
 * param_twigcount threshold.
 */
CREATE TYPE type_category_path_info AS (id INTEGER, parent INTEGER, shortname VARCHAR(255), title VARCHAR(255), twig BOOLEAN);

CREATE OR REPLACE FUNCTION getCategoryPathInfo(INTEGER, INTEGER) RETURNS SETOF type_category_path_info AS $$
	DECLARE
		param_id ALIAS FOR $1;
		param_twig_threshold ALIAS FOR $2;
		local_id INTEGER;
		local_count INTEGER;
		local_twig BOOLEAN;
		returned_row type_category_path_info%ROWTYPE;
	BEGIN
		local_id := param_id;
		WHILE local_id is not null LOOP
			BEGIN
				local_twig = FALSE;

				SELECT INTO local_count count(id) FROM Category WHERE parent = local_id;

				IF local_count != 0 THEN

					SELECT INTO local_count count(id) FROM Category WHERE parent IN (SELECT id FROM Category WHERE parent = local_id);

					IF local_count = 0 THEN
						SELECT INTO local_count count(product) FROM CategoryProductBinding WHERE category IN (SELECT id FROM Category WHERE parent = local_id);

						IF local_count < param_twig_threshold THEN
							local_twig = TRUE;
						END IF;
					END IF;
				END IF;

				SELECT INTO returned_row id, parent, shortname, title, local_twig
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
