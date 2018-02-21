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

		var parts = {};
		for (var i = 0; i < place.address_components.length; i++) {
			var addressType = place.address_components[i].types[0];
			parts[addressType] = place.address_components[i].short_name;
		}

		var line1 = '';
		if (parts.route) {
			line1 = (parts.street_number)
				? parts.street_number + ' ' + parts.route
				: parts.route;
		}

		if (line1) {
			setValue('address_line1', line1);
		}

		var establishment = getEstablishment(place);
		if (establishment) {
			setValue('address_line2', establishment);
		} else {
			setValue('address_line2', '');
		}

		if (parts.locality) {
			setValue('address_city', parts.locality);
		} else if (parts.sublocality_level_1) {
			// Brooklyn, NYC doesn't use parts.locality
			setValue('address_city', parts.sublocality_level_1);
		} else if (parts.administrative_area_level_1) {
			// Istanbul uses administrative_area_level_1
			setValue('address_city', parts.administrative_area_level_1);
		} else {
			setValue('address_city', '');
		}

		if (parts.postal_code) {
			setValue('address_postalcode', parts.postal_code);
		} else {
			setValue('address_postalcode', '');
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

	autocomplete.addListener('place_changed', fillInAddress);
}

StoreGoogleAddressAutoComplete.prov_states = [];
