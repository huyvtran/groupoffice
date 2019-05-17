/* global go */

go.Modules.register("community", "addressbook", {
	mainPanel: "go.modules.community.addressbook.MainPanel",
	title: t("Address book"),
	entities: [{

			name: "Contact",
			hasFiles: true, //Rename to files?
			customFields: {
				fieldSetDialog: "go.modules.community.addressbook.CustomFieldSetDialog"
			},

			relations: {
				organizations: {store: "Contact", fk: "organizationIds"},
				creator: {store: "User", fk: "createdBy"},
				modifier: {store: "User", fk: "createdBy"}
			},

			/**
			 * Filter definitions
			 * 
			 * Will be used by query fields where you can use these like:
			 * 
			 * name: Piet,John age: < 40
			 * 
			 * Or when adding custom saved filters.
			 */
			filters: [
				{
					name: 'text',
					type: "string",
					multiple: false,
					title: "Query"
				},
				{
					name: 'name',
					title: t("Name"),
					type: "string",
					multiple: true
				},
				{
					name: 'email',
					title: t("E-mail"),
					type: "string",
					multiple: true
				}, {
					name: 'phone',
					title: t("Phone"),
					type: "string",
					multiple: true
				}, {
					name: 'country',
					title: t("Country"),
					type: "string",
					multiple: true
				}, {
					name: 'city',
					title: t("City"),
					type: "string",
					multiple: true
				}, {
					name: 'gender',
					title: t("Gender"),
					type: "select",
					multiple: true,
					options: [{
							value: 'M',
							title: t("Male"),
						}, {
							value: 'F',
							title: t("Female")
						}, {
							value: null,
							title: t("Unknown")
						}]
				},
				{
					title: t("Modified at"),
					name: 'modified',
					multiple: false,
					type: 'date'
				},
				{
					title: t("Age"),
					name: 'age',
					multiple: false,
					type: 'number'
				},
				{
					title: t("Birthday"),
					name: 'birthday',
					multiple: false,
					type: 'date'
				}, {
					title: t("User group"),
					name: 'usergroupid',
					multiple: true,
					type: 'go.groups.GroupCombo'
				}, {
					title: t("Is a user"),
					name: 'isUser',
					multiple: false,
					type: 'select',
					options: [
						{
							value: true,
							title: t("Yes")
						},
						{
							value: false,
							title: t("No")
						}
					]
				}
			],
			links: [{

					filter: "isContact",

					iconCls: "entity ic-person",

					/**
					 * Opens a dialog to create a new linked item
					 * 
					 * @param {string} entity eg. "Note"
					 * @param {string|int} entityId
					 * @returns {go.form.Dialog}
					 */
					linkWindow: function (entity, entityId) {
						return new go.modules.community.addressbook.ContactDialog();
					},

					/**
					 * Return component for the detail view
					 * 
					 * @returns {go.detail.Panel}
					 */
					linkDetail: function () {
						return new go.modules.community.addressbook.ContactDetail();
					}
				}, {
					/**
					 * Entity name
					 */
					title: t("Organization"),

					iconCls: "entity ic-business",

					filter: "isOrganization",

					/**
					 * Opens a dialog to create a new linked item
					 * 
					 * @param {string} entity eg. "Note"
					 * @param {string|int} entityId
					 * @returns {go.form.Dialog}
					 */
					linkWindow: function (entity, entityId) {
						var dlg = new go.modules.community.addressbook.ContactDialog();
						dlg.setValues({isOrganization: true});
						return dlg;
					},

					/**
					 * Return component for the detail view
					 * 
					 * @returns {go.detail.Panel}
					 */
					linkDetail: function () {
						return new go.modules.community.addressbook.ContactDetail();
					}
				}]

		}, {
			name: "AddressBook",
			title: t("Address book"),
			isAclOwner: true
		}, "AddressBookGroup"],

	userSettingsPanels: [
		"go.modules.community.addressbook.SettingsPanel",
		"go.modules.community.addressbook.SettingsProfilePanel"
	]
});


go.modules.community.addressbook.typeStoreData = function (langKey) {
	var types = [], typeLang = t(langKey);

	for (var key in typeLang) {
		types.push([key, typeLang[key]]);
	}
	return types;
};

//go.Db.store("User");


Ext.onReady(function () {
	if(!go.modules.business || !go.modules.business.newsletters) {
		return;
	}
	
	go.modules.business.newsletters.registerEntity({
		name: "Contact",
		grid: go.modules.community.addressbook.ContactGrid,
		add: function () {		
			return new Promise(function (resolve, reject) {
				var select = new go.modules.community.addressbook.SelectDialog({
					mode: 'id',
					scope: this,					
					selectMultiple: function (ids) {
						this.resolved = true;
						resolve(ids);
					},
					listeners: {
						close: function() {
							if(!this.resolved) {
								reject();
							}
						}
					}
				});
				select.show();
			});
		}
	});
});