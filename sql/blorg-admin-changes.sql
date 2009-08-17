-- Site Content is set up in admin-changes.sql, this should be run after it

insert into AdminComponent (id, section, displayorder, shortname, title, description, enabled, visible)
	values (402, 104, 30, 'Post', 'Blog Posts', null, true, true);

insert into AdminComponent (id, section, displayorder, shortname, title, description, enabled, visible)
	values (403, 104, 40, 'Comment', 'Manage Blog Comments', null, true, true);

insert into AdminComponent (id, section, displayorder, shortname, title, description, enabled, visible)
	values (404, 104, 50, 'Tag', 'Blog Tags', null, true, true);

insert into AdminComponent (id, section, displayorder, shortname, title, description, enabled, visible)
	values (405, 104, 60, 'Author', 'Blog Authors', null, true, true);

select setval('adminsection_id_seq', max(id)) from AdminSection;
select setval('admincomponent_id_seq', max(id)) from AdminComponent;
select setval('adminsubcomponent_id_seq', max(id)) from AdminSubComponent;

-- silverorange group
insert into AdminComponentAdminGroupBinding (component, groupnum)
select id, 1 from AdminComponent where id not in (
	select component from AdminComponentAdminGroupBinding);

-- administrator group
insert into AdminComponentAdminGroupBinding (component, groupnum)
select id, 100 from AdminComponent where id not in (
select component from AdminComponentAdminGroupBinding);
