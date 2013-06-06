<strong>FEATURES</strong>

Template Info is a simple extension that displays basic template information about the primary template being rendered.

<ul>
  <li>Template ID</li>
  <li>Template Name</li>
  <li>Template Group ID</li>
  <li>Template Group Name</li>
</ul>
<br>
<strong>USAGE</strong>

Just add one or all of the following tags to your template:

<pre>
{template_info:template_id}<br>
{template_info:template_name}<br>
{template_info:template_group_id}<br>
{template_info:template_group_name}<br>
</pre>
<br>
<strong>REQUIREMENTS</strong>

Template Info is a extension tested for ExpressionEngine 2.0 or greater.

<br>
<strong>INSTALLATION</strong>

The Template Info extension contains a single file. Please following these steps:

1. Download the latest version of the extension
2. Extract the .zip file to your desktop
3. Copy the template_info directory to your /system/expressionengine/third_party/ folder

<br>
<strong>THANKS</strong>

A huge thanks to Leevi Graham @leevigraham for his permission to use the LGTemplateInfo plugin and port it EE 2.x.

Leevi's original plugin for EE 1.x can be found here: http://leevigraham.com/cms-customisation/expressionengine/addon/lg-template-info/

<br>
<strong>CHANGE LOG</strong>

v2.0.0 - Added template info as early parsed global variables. 
	   - Change from a plugin to an extension.
 		  
v1.1.0 - Added < EE 2.6.0 backward compatibility.

v1.0.2 - Fixed an issue where template_group_name was never being set. Also added some 404 love.
 
v1.0.1 - Fixed an issue where URI's where not matching due to a leading slash missing.

v1.0.0 - Initial release