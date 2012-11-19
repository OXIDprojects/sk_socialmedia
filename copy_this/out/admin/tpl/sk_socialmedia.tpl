[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]
[{ if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]


<script type="text/javascript">
<!--
window.onload = function ()
{
    [{ if $updatelist == 1}]
        top.oxid.admin.updateList('[{ $oxid }]');
    [{ /if}]
    top.reloadEditFrame();
}
function editThis( sID )
{
    var oTransfer = top.basefrm.edit.document.getElementById( "transfer" );
    oTransfer.oxid.value = sID;
    oTransfer.cl.value = top.basefrm.list.sDefClass;

    //forcing edit frame to reload after submit
    top.forceReloadingEditFrame();

    var oSearch = top.basefrm.list.document.getElementById( "search" );
    oSearch.oxid.value = sID;
    oSearch.actedit.value = 0;
    oSearch.submit();
}
function processUnitInput( oSelect, sInputId )
{
    document.getElementById( sInputId ).disabled = oSelect.value ? true : false;
}
//-->
</script>

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="sk_socialmedia">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="sk_socialmedia">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="oxparentid" value="[{ $oxparentid }]">
<input type="hidden" name="editval[article__oxid]" value="[{ $oxid }]">



<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
    <tr height="10">
      <td></td><td></td>
    </tr>
    <tr>
		<td width="15"></td>
		<td valign="top" class="edittext">

        <table cellspacing="0" cellpadding="0" border="0">
			[{ if $oxparentid }]
			<tr>
				<td class="edittext" width="120">
					<b>[{ oxmultilang ident="GENERAL_VARIANTE" }]</b>
				</td>
				<td class="edittext">
					<a href="Javascript:editThis('[{ $parentarticle->oxarticles__oxid->value}]');" class="edittext"><b>[{ $parentarticle->oxarticles__oxartnum->value }] [{ $parentarticle->oxarticles__oxtitle->value }]</b></a>
				</td>
			</tr>
			[{ /if}]
			<tr>
				<td class="edittext" width="140">
					[{ oxmultilang ident="ARTICLE_EXTEND_POST2FB" }]
				</td>
				<td class="edittext">
					<input type="hidden" name="editval[oxarticles__fbpublished]" value='0'>
					<input class="edittext" type="checkbox" name="editval[oxarticles__fbpublished]" value='1' [{if $edit->oxarticles__fbpublished->value == 1}]checked[{/if}]>
					[{ oxinputhelp ident="HELP_ARTICLE_EXTEND_POST2FB" }]
				</td>
			</tr>
			<tr>
				<td class="edittext" width="140">
					[{ oxmultilang ident="ARTICLE_EXTEND_TWEET" }]
				</td>
				<td class="edittext">
					<input type="hidden" name="editval[oxarticles__tweetpublished]" value='0'>
					<input class="edittext" type="checkbox" name="editval[oxarticles__tweetpublished]" value='1' [{if $edit->oxarticles__tweetpublished->value == 1}]checked[{/if}]>
					[{ oxinputhelp ident="HELP_ARTICLE_EXTEND_TWEET" }]
				</td>
			</tr>
			<tr>
				<td class="edittext" width="140">
					[{ oxmultilang ident="ARTICLE_EXTEND_DONTPUBLISHFB" }]
				</td>
				<td class="edittext">
					<input type="hidden" name="editval[oxarticles__smdontpublish]" value='0'>
					<input class="edittext" type="checkbox" name="editval[oxarticles__smdontpublish]" value='1' [{if $edit->oxarticles__smdontpublish->value == 1}]checked[{/if}]>
					[{ oxinputhelp ident="HELP_ARTICLE_EXTEND_DONTPUBLISHFB" }]
				</td>
			</tr>
			<tr>
            <td class="edittext"></td>
            <td class="edittext">
              <input type="submit" class="edittext" name="save" value="[{ oxmultilang ident="GENERAL_SAVE" }]" onClick="Javascript:document.myedit.fnc.value='save'"" ><br>
            </td>
          </tr>
		</table>
    </tr>
</table>


</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]