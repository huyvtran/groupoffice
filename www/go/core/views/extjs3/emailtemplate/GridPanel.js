
go.emailtemplate.GridPanel = Ext.extend(go.grid.GridPanel, {
	module: null,
	viewConfig: {
		emptyText: 	'<p>' +t("No items to display") + '</p>'
	},

	initComponent: function () {

		this.viewConfig.actionConfig = {
			scope: this,
			menu: {
				items: [
					{
						itemId: "edit",
						iconCls: 'ic-edit',
						text: t("Edit"),
						handler: function(item) {

							var record = this.store.getAt(item.parentMenu.rowIndex);

							this.edit(record.data.id);

						},
						scope: this
					},{
						itemId: "delete",
						iconCls: 'ic-delete',
						text: t("Delete"),
						handler: function(item) {
							var record = this.store.getAt(item.parentMenu.rowIndex);
							this.getSelectionModel().selectRecords([record]);
							this.deleteSelected();
						},
						scope: this
					}

				]
			}
		};

		Ext.apply(this, {	
			tbar: [
				// {
				// 	xtype:'tbtitle',
				// 	text: t("E-mail templates")
				// },
				'->',
				{
					xtype: 'tbsearch'
				},
				{
					iconCls: 'ic-add',
					handler: function() {
						var dlg = new go.emailtemplate.TemplateDialog();
						dlg.setValues({module: this.module}).show();
					},
					scope: this
			}],
			
			store: new go.data.Store({
				fields: ['id', 'name', 'language'],
				entityStore: "EmailTemplate",
				filters: {
					module: {module: this.module}
				}	
			}),
			autoHeight: true,
			columns: [
				{
					id: 'name',
					header: t('Name'),
					sortable: false,
					dataIndex: 'name',
					hideable: false,
					draggable: false,
					menuDisabled: true
				},
				 {
					id:'language',
					header:t("Language"),
					dataIndex: "language",
					sortable: true
				}
			],
			listeners : {
				render: function() {
					this.store.load();
				},
				scope: this
			},
			autoExpandColumn: "name"
		});

		go.smtp.GridPanel.superclass.initComponent.call(this);
		
		this.on("rowdblclick", function(grid, rowIndex, e) {
			var record = grid.getStore().getAt(rowIndex);
			this.edit(record.data.id);
		}, this);
	},

	edit: function(id) {
		var dlg = new go.emailtemplate.TemplateDialog();
		dlg.load(id).show();
	}
});