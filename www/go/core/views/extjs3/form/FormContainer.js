/**
 * FormContainer
 * 
 * A form container is a group of fields that acts like a single form field. 
 * It returns an object with all it's child form fields a members.
 * 
 * See also FormGroup for returning an array.
 */
go.form.FormContainer = Ext.extend(Ext.Container, {
	layout: "form",

	name: null,

	isFormField: true,

	origValue: null,

	initComponent: function () {
		this.origValue = {};
		this.additionalFields = [];
		go.form.FormContainer.superclass.initComponent.call(this);

		this.on("add", function (e) {
			//to prevent adding to Ext.form.BasicForm with add event.
			//Cancels event bubbling
			return false;
		});


	},

	getName: function () {
		return this.name;
	},

	addAdditionalField: function (f) {
		this.additionalFields.push(f);
	},

	getAllFormFields: function () {
	
		//use slice to obtain copy
		var fields = this.additionalFields.slice(), fn = function (f) {
			if (f.isFormField && !f.isComposite && f.getXType() != 'checkboxgroup') {
				fields.push(f);
			} else if (f.items) {
				if (f.items.each) {
					//Ext.util.Collection
					f.items.each(fn);
				} else
				{
					//native array
					f.items.forEach(fn);
				}
			}
		};

		this.items.each(fn);

		
		return fields;
	},
	findField: function (id) {

		//searches for the field corresponding to the given id. Used recursively for composite fields
		var field = false, findMatchingField = function (f) {
			if (f.dataIndex == id || f.id == id || f.getName() == id) {
				field = f;
				return false;
			}
		};

		this.getAllFormFields().forEach(findMatchingField);

		return field || null;
	},

	isDirty: function () {
		var dirty = false, fn = function (i) {
			if (i.isDirty && i.isDirty()) {
				dirty = true;
				//stops iteration
				return false;
			}
		};
		this.getAllFormFields().forEach(fn, this);

		return dirty;
	},

	reset: function () {
		this.setValue({});
	},

	setValue: function (v) {
		this.origValue = v;

		for (var name in v) {
			var field = this.findField(name);
			if (field) {
				field.setValue(v[name]);
			}
		}

	},

	getValue: function (dirtyOnly) {
		var v = dirtyOnly ? {} : this.origValue, val;

		var fn = function (f) {

			if ((!dirtyOnly || f.isDirty())) {

				if (f.getXType() == 'numberfield') {
					f.serverFormats = false; // this will post number as number
				}

				val = f.getValue();

				if (Ext.isDate(val)) {
					val = val.serialize();
				}

				v[f.getName()] = val;
			}
		};

		this.getAllFormFields().forEach(fn, this);

		return v;
	},

	markInvalid: function (msg) {
		this.getAllFormFields().forEach(function (i) {
			i.markInvalid(msg);
		});
	},

	clearInvalid: function () {
		this.getAllFormFields().forEach(function (i) {
			i.clearInvalid();
		});
	},

	validate: function () {
		var valid = true, fn = function (i) {
			if (i.isFormField && !i.validate()) {
				valid = false;
				//stops iteration
				return false;
			}
		};
		this.getAllFormFields().forEach(fn, this);

		return valid;
	},

	focus: function () {
		var fields = this.getAllFormFields();
		var firstFormField = fields.length ? fields[0] : false;

		if (firstFormField) {
			firstFormField.focus();
		} else
		{
			go.form.FormContainer.superclass.focus.call(this);
		}
	}
});

Ext.reg('formcontainer', go.form.FormContainer);