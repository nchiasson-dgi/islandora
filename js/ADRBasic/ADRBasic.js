/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

Ext.onReady(function(){
    $('#content-fedora').empty();
    Ext.QuickTips.init();
    var adrbasic = new ADRBasicViewer({
        renderTo: 'content-fedora'
    });
    adrbasic.show();
});