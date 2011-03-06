/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

Ext.onReady(function(){
    $('#content-fedora').empty();
    //$('#content-fedora').append('<br/>');
    Ext.QuickTips.init();
    var adrbasic = new ADRBasicViewer({
        renderTo: 'content-fedora'
    });
    adrbasic.show();

    /*var width = 960;
    var description = {
        layout: 'table',
        unstyled: true,
        layoutConfig: {
            columns: 2
        },
        defaults: {
            border: false,
            bodyStyle:'padding:20px'
        },
        items: [{
            html: 'Title',
            cellCls: 'overview-label'
        }, {
            contentEl: 'fedora-object-title',
            cellCls: 'overview-value'
        }, {
            html: 'Author',
            cellCls: 'overview-label'
        }, {
            contentEl: 'fedora-object-author',
            cellCls: 'overview-value'
        },{
            html: 'Description',
            cellCls: 'overview-label'
        }, {
            contentEl: 'fedora-object-description',
            cellCls: 'overview-value'
        }, {
            contentEl: 'fedora-object-date-info',
            cellCls: 'overview-date-info',
            colspan: 2
        }]
    };

    var files = {
        unstyled: true,
        width: 100,
        xtype: 'panel',
        html: '<p>Content</p>'
    };

    var overview = {
        id: 'fedora-object-overview',
        title: 'Overview',
        layout: 'table',
        layoutConfig: {
            columns: 2
        },
        defaults: {
            bodyStyle:'padding:15px 20px',
            border: false
        },
        items: [ description, files]
    };

    var viewer = {
        title: 'Viewer',
        html: ''
    };
    
    var manage = {
        title: 'Manage',
        layout: 'table',
        shadow: true,
        frame: false,
        bodyBorder: false,
        layoutConfig: {
            columns: 1
        },
        defaults: {
            bodyStyle:'padding:0px 20px',
            border: false
        },
        items: [{
            title: 'Edit Object',

        },{
            title: 'Edit Description',

        },{
            title: 'Manage Files',
        }]
    };

    new Ext.TabPanel({
        activeTab: 0,
        width: width,
        plain: true,
        layoutOnTabChange:true,
        defaults: {
            border: false,
            frame: false
        },
        items: [ overview, viewer, manage ],
        renderTo: 'content-fedora'
    });
    //$('#content-fedora > *:not(.x-tab-panel)').remove();
    $('#content-fedora').prepend('<br/><br/>');*/
});