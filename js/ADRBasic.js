/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

Ext.onReady(function(){
    new Ext.TabPanel({
        activeTab: 0,
        width: 960,
        plain: true,
        layoutOnTabChange:true,
        defaults: {
            border: false,
            frame: false,
            autoHeight: true
        },
        items: [{
            id: 'fedora-object-overview',
            title: 'Overview',
            layout: 'table',
            layoutConfig: {
                columns: 2,
            },
            defaults: {
                autoHeight: true,
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
        },{
            title: 'Manage',
            html: '<br/><br/><br/><br/>'
        }],
        renderTo: 'content-fedora'
    });
    $('#content-fedora > *:not(.x-tab-panel)').remove();
    $('#content-fedora').prepend('<br/><br/>');
});