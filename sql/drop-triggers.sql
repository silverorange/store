/*
 * Run this SQL to delete triggers.
 * psql -d veseys2 -U php -f drop-triggers.sql
 */

drop trigger accountdeletetrigger on account;
drop trigger adreferrerinserttrigger on adreferrer;
drop trigger catalogdeletetrigger on catalog;
drop trigger categoryvisibleproductcountbyregiontrigger on visibleproductcache;
drop trigger categoryvisibleproductcountbyregiontrigger on category;
drop trigger orderdeletetrigger on orders;
drop trigger visibleproducttrigger on itemregionbinding;

drop trigger adminuserhistoryinserttrigger on adminuserhistory;
drop trigger visibleproducttrigger on catalogregionbinding;
drop trigger visibleproducttrigger on categoryproductbinding;
drop trigger visibleproducttrigger on item;
drop trigger orderupdatetrigger on orders;
drop trigger orderinserttrigger on orders;

--select tgname, relname from pg_class inner join pg_trigger on (tgrelid = relfilenode)

