
/* global go, Ext */

/**
 * 
 * 
 * //Inserting records will trigger server update too:
 * var store = this.noteGrid.store;
						var myRecordDef = Ext.data.Record.create(store.fields);

						store.insert(0, new myRecordDef({
							name: "New",
							content: "Testing",
							noteBookId: this.addNoteBookId
						}));
						
						store.commitChanges();
 */
go.data.Store = Ext.extend(Ext.data.JsonStore, {

	autoDestroy: true,
	
	autoSave: false,
	
	remoteSort : true,
	
	constructor: function (config) {
		
		config = config || {};
		config.root = "records";

		Ext.applyIf(this, go.data.StoreTrait);
		
		this.addCustomFields(config);
		
		go.data.Store.superclass.constructor.call(this, Ext.applyIf(config, {
			idProperty:  "id",
			paramNames: {
				start: 'position', // The parameter name which specifies the start row
				limit: 'limit', // The parameter name which specifies number of rows to return
				sort: 'sort', // The parameter name which specifies the column to sort on
				dir: 'dir'       // The parameter name which specifies the sort direction
			},
			proxy: config.entityStore ? new go.data.EntityStoreProxy(config) : new go.data.JmapProxy(config)
		}));        
		
		this.setup();		
	},
	
	loadData : function(o, append){
		var old = this.loading;
		this.loading = true;
			
		if(this.proxy instanceof go.data.EntityStoreProxy) {
			this.proxy.preFetchEntities(o.records, function() {
				go.data.Store.superclass.loadData.call(this, o, append);	
				this.loading = old;		
			}, this);
		} else
		{
			go.data.Store.superclass.loadData.call(this, o, append);	
			this.loading = old;
		}
	},
	
	sort : function(fieldName, dir) {
		//Reload first page data set on sort
		if(this.lastOptions && this.lastOptions.params) {
			this.lastOptions.params.position = 0;
			this.lastOptions.add = false;
		}
		
		return go.data.Store.superclass.sort.call(this, fieldName, dir);
	},
	
	destroy : function() {	
		this.fireEvent('beforedestroy', this);
		
		go.data.Store.superclass.destroy.call(this);
		
		this.fireEvent('destroy', this);
	},
	
	
	
	//override Extjs writer save for entityStore
	save: function(cb) {
		var queue = {},
			 rs = this.getModifiedRecords(),
			 hasChanges = false;
		if(this.removed.length){
			hasChanges = true;
			queue.destroy = [];
			for(var r,i = 0; r = this.removed[i]; i++){
				queue.destroy.push(r.id);
			}
		}
		if(rs.length){
			hasChanges = true;
			queue.create = {};
			queue.update = {};
			for(var r,i = 0;r = rs[i]; i++){
				if(!r.isValid()) {
					continue;
				}
				var change = {};
				for(attr in r.modified) {
					change[attr] = r.data[attr];
				}
				queue[r.phantom?'create':'update'][r.id] = change;
			}
		}
		if(hasChanges) {
			if(this.fireEvent('beforesave', this, queue) !== false){
				//console.log(queue);
				this.entityStore.set(queue, function(options, success, queue){
					this.commitChanges();
					if(cb) {cb(success) }
				},this);
			}
		}
	},
	

});

Ext.reg('gostore', go.data.Store);