create or replace view SavedCartEntryView as
	select * from CartEntry where saved = true;

