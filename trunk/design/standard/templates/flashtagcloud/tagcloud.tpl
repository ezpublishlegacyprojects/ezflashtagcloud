{set-block variable=$xmlTagCloud}
<tags>
{foreach $tag_cloud as $tag}
	<a href={concat( '/content/keyword/', $tag['tag']|rawurlencode )|ezurl('single')} style='font-size: {$tag['font_size']}pt'>{$tag['tag']|wash()}</a> 
{/foreach}
</tags>
{/set-block}

{* SEO *}
<div id="eztagcloudflash">
	<p style="display:none;">
	{foreach $tag_cloud as $tag}
		<a href={concat( '/content/keyword/', $tag['tag']|rawurlencode )|ezurl('single')} style='font-size: {$tag['font_size']}pt'>{$tag['tag']|wash()}</a> 
	{/foreach}
	</p>
</div>
<script type="text/javascript">
var ezflashtagcloud = new SWFObject({'flash/tagcloud.swf'|ezdesign}, "eztagcloudflash", "{$width}", "{$height}", "9", "#{ezini('FlashSettings','BackgroundColor','ezflashtagcloud.ini')}");
ezflashtagcloud.addParam("allowScriptAccess", "always");
{if eq(ezini('FlashSettings','Transparent','ezflashtagcloud.ini'),'true')}ezflashtagcloud.addParam("wmode", "transparent");{/if}
ezflashtagcloud.addVariable("tcolor", "0x{ezini('FlashSettings','Tcolor','ezflashtagcloud.ini')}");
ezflashtagcloud.addVariable("tcolor2", "0x{ezini('FlashSettings','Tcolor2','ezflashtagcloud.ini')}");
ezflashtagcloud.addVariable("hicolor", "0x{ezini('FlashSettings','Hicolor','ezflashtagcloud.ini')}");
ezflashtagcloud.addVariable("tspeed", "{ezini('FlashSettings','Tspeed','ezflashtagcloud.ini')}");
ezflashtagcloud.addVariable("distr", "{ezini('FlashSettings','Distr','ezflashtagcloud.ini')}");
ezflashtagcloud.addVariable("mode", "tags");
ezflashtagcloud.addVariable("tagcloud", "{$xmlTagCloud}");
ezflashtagcloud.write("eztagcloudflash");
</script>