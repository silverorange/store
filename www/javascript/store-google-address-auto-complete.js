function StoreGoogleAddressAutoComplete(prefix)
{
	if (prefix) {
		prefix += '_';
	}

	// Auto-complete only works with input tags, not textarea
	var input = document.getElementById(prefix + 'address_line1');
	if (input && input.tagName !== 'INPUT') {
		return;
	}

	var autocomplete = new google.maps.places.Autocomplete(input);

	// prevent "enter" from submitting form
	google.maps.event.addDomListener(input, 'keydown', function(event) {
		if (event.keyCode === 13) {
			event.preventDefault();
		}
	});

	function setValue(name, value) {
		document.getElementById(prefix + name).value = value;
	}

	// Only return an establishment like "Googleplex" if the address
	// isn't just a normal address. Auto-complete returns place.name
	// for all addresses, but it's only useful for POI and
	// establishments (aka businesses).
	function getEstablishment(place) {
		var establishment = '';

		for (var i = 0; i < place.types.length; i++) {
			if (place.name &&
				place.types[i] === 'establishment' ||
				place.types[i] === 'point_of_interest') {
				establishment = place.name;
				break;
			}
		}

		return establishment;
	}

	function fillInAddress() {
		var place = autocomplete.getPlace();
		var components = place.address_components;
		if (!components || components.length === 0) {
			return;
		}

		// reset address parts
		setValue('address_line2', '');
		setValue('address_provstate_entry', '');
		setValue('address_city', '');
		setValue('address_postalcode', '');

		var parts = {};
		var parts_long = {};
		for (var i = 0; i < place.address_components.length; i++) {
			var addressType = place.address_components[i].types[0];
			parts[addressType] = place.address_components[i].short_name;
			parts_long[addressType] = place.address_components[i].long_name;
		}

		var line1 = '';
		if (parts.route) {
			line1 = (parts.street_number)
				? parts.street_number + ' ' + parts.route
				: parts.route;
		}

		if (line1 != '') {
			setValue('address_line1', line1);
		}

		var establishment = getEstablishment(place);
		if (establishment) {
			setValue('address_line2', establishment);
		}

		if (parts_long.locality) {
			setValue('address_city', parts_long.locality);
		} else if (parts.postal_town) {
			setValue('address_city', parts_long.postal_town);
		} else if (parts_long.sublocality_level_1) {
			// Brooklyn, NYC doesn't use parts.locality
			setValue('address_city', parts_long.sublocality_level_1);
		} else if (parts_long.administrative_area_level_1) {
			// Istanbul uses administrative_area_level_1 as city
			setValue('address_city', parts_long.administrative_area_level_1);
		}

		if (parts.postal_code) {
			setValue('address_postalcode', parts.postal_code);
		}

		if (parts.country) {
			var select = document.getElementById(prefix + 'address_country');
			for (var i = 0; i < select.options.length; i++) {
				if (select[i].value === parts.country) {
					select.selectedIndex = i;

					// fire event to make the cascade select change
					select.dispatchEvent(new Event('change'));
					break;
				}
			}
		}

		var code = parts.administrative_area_level_1;
		if (parts.country && code) {
			var id = false;
			var ids = StoreGoogleAddressAutoComplete.prov_states;
			for (var i = 0; i < ids.length; i++) {
				if (ids[i].country === parts.country && ids[i].code == code) {
					id = ids[i].id;
				}
			}

			// Great Britian returns "England" for administrative_area_level_1
			if (id === false && parts.country !== 'GB') {
				setValue(
					'address_provstate_entry',
					parts_long.administrative_area_level_1
				);
			} else {
				var select = document.getElementById(
					prefix + 'address_provstate_flydown'
				);

				for (var i = 0; i < select.options.length; i++) {
					if (select[i].value == id) {
						select.selectedIndex = i;
						break;
					}
				}
			}
		}
	}

	autocomplete.addListener('place_changed', fillInAddress);
}

StoreGoogleAddressAutoComplete.prov_states = [];
