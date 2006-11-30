-- Note - like all sql in this directory, this should only be viewed as a
-- template for a local sites version of admin-changes.sql

-- Product Management
INSERT INTO AdminSection (id, displayorder, title, description, show)
	VALUES (101, 10, 'Product Management', NULL, true);

INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (100, 101, 10, 'Category', 'Product Categories', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (101, 101, 20, 'Product', 'Product Search', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (102, 101, 30, 'Catalog', 'Catalogues', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (103, 101, 0, 'Item', 'Items', NULL, true, false);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (104, 101, 0, 'ItemGroup', 'Item Groups', NULL, true, false);

-- Sales
INSERT INTO AdminSection (id, displayorder, title, description, show)
	VALUES (102, 20, 'Sales', NULL, true);

INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (200, 102, 10, 'Account', 'Customer Accounts', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (201, 102, 20, 'Order', 'Orders', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (204, 102, 50, 'Ad', 'Ads', NULL, true, true);

-- Reports
INSERT INTO AdminSection (id, displayorder, title, description, show)
	VALUES (103, 30, 'Reports', NULL, true);

INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (300, 103, 20, 'WebStat', 'Web Stats', NULL, true, true);

-- Site Content
INSERT INTO AdminSection (id, displayorder, title, description, show)
	VALUES (104, 40, 'Site Content', null, true);

INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (400, 104, 10, 'Article', 'Articles', NULL, true, true);
INSERT INTO AdminSubComponent (id, component, title, shortname, show, displayorder)
	VALUES (400, 400, 'Search', 'Search', true, 0);

-- Store Settings
INSERT INTO AdminSection (id, displayorder, title, description, show)
	VALUES (105, 50, 'Store Settings', null, true);

INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (501, 105, 70, 'Locale', 'Locales', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (502, 105, 80, 'ProvState', 'Provinces & States', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (503, 105, 90, 'Region', 'Regions', NULL, true, true);
INSERT INTO AdminComponent (id, section, displayorder, shortname, title, description, enabled, show)
	VALUES (504, 105, 120, 'Country', 'Countries', NULL, true, true);

SELECT setval('adminsection_id_seq', max(id)) FROM AdminSection;
SELECT setval('admincomponent_id_seq', max(id)) FROM AdminComponent;
SELECT setval('adminsubcomponent_id_seq', max(id)) FROM AdminSubComponent;

INSERT INTO AdminGroup (id, title) VALUES (100, 'Administrator');
SELECT setval('admingroup_id_seq', max(id)) FROM AdminGroup;

--INSERT INTO AdminUser (id, username, name, password, enabled)
--	VALUES (100, 'username', 'Name', '171aca01f9db0b126252dc51382fe1d0', true);
SELECT setval('adminuser_id_seq', max(id)) FROM AdminUser;

--INSERT INTO AdminUserAdminGroupBinding (usernum, groupnum) VALUES (100, 100);

TRUNCATE TABLE AdminComponentAdminGroupBinding;

-- silverorange group
INSERT INTO AdminComponentAdminGroupBinding (component, groupnum) SELECT id, 1 FROM AdminComponent;

-- administrator group
INSERT INTO AdminComponentAdminGroupBinding (component, groupnum) SELECT id, 100 FROM AdminComponent;
DELETE FROM AdminComponentAdminGroupBinding WHERE groupnum = 100
	AND component IN (1,2,3,4,5);
