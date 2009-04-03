create table ProductAttributeBinding (
	product int not null references Product(id) on delete cascade,
	attribute int not null references Attribute(id) on delete cascade,
	primary key (product, attribute)
);

CREATE INDEX ProductAttributeBinding_product_index ON ProductAttributeBinding(product);
CREATE INDEX ProductAttributeBinding_attribute_index ON ProductAttributeBinding(attribute);
