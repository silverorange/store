CREATE OR REPLACE FUNCTION clearCache () RETURNS INTEGER AS $$
	BEGIN
		truncate visibleproductcache;
		insert into visibleproductcache
			select * from visibleproductview; 

		truncate categoryvisibleproductcountbyregioncache;
		insert into categoryvisibleproductcountbyregioncache
			select *  from categoryvisibleproductcountbyregionview  where category is not null;

		truncate categoryvisiblemajorproductcountbyregioncache;
		insert into categoryvisiblemajorproductcountbyregioncache
			select *  from categoryvisiblemajorproductcountbyregionview  where category is not null;

		RETURN 0;
	END;
$$ LANGUAGE 'plpgsql';
