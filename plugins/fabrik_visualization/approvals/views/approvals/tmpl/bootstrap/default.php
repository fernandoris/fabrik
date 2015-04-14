<?php
/**
 * Approval Viz: Default Tmpl
 *
 * @package		Joomla.Plugin
 * @subpackage	Fabrik.visualization.approvals
 * @copyright	Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//@TODO if we ever get calendars inside packages then the ids will need to be
// Replaced with classes contained within a distinct id

$app = JFactory::getApplication();
$package = $app->getUserState('com_fabrik.package', 'fabrik');
$row = $this->row;
?>

<div id="<?php echo $this->containerId; ?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title', 0))
{ ?>
		<h1><?php echo $row->label; ?></h1>
	<?php } ?>
	<table class="table table-stripped">
		<thead>
			<tr class="">
				<th><?php echo 'Type';//FText::_('PLG_VIZ_APPROVALS_TYPE') ?></th>
				<th><?php echo 'Title';//FText::_('PLG_VIZ_APPROVALS_TITLE') ?></th>
				<th><?php echo 'User';//FText::_('PLG_VIZ_APPROVALS_USER') ?></th>
				<th style="width:15%;text-align:center"><?php echo 'View';//FText::_('PLG_VIZ_APPROVALS_VIEW') ?></th>
				<th style="width:15%;text-align:center"><?php echo 'Approve';//FText::_('PLG_VIZ_APPROVALS_APPROVE') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
foreach ($this->rows as $row)
{
	$url = 'index.php?option=com_' . $package . '&controller=visualization.approvals&view=visualization&format=raw&visualizationid='
 	. $this->id . '&listid=' . $row->listid . '&rowid=' . $row->rowid . '&plugintask=' ;

			   ?>
				<tr>
					<td><?php echo $row->type ?></td>
					<td><?php echo $row->title ?></td>
					<td><?php echo $row->user ?></td>
					<td  style="text-align:center">
					<a href="<?php echo $row->view ?>">
					<a class="fabrikTip" opts="{position:'right'}" title="<?php echo FabrikString::truncate($row->content,
		array('tip' => false, 'wordcount' => 200)) ?>" >
						<i class="icon-search"></i>
					</a></td>
					<td>
						<div class="btn-group">
							<a class="dropdown-toggle btn btn-mini" data-toggle="dropdown" href="#">
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu">
								<li>
									<a class="approve" href="<?php echo $url . 'approve'?>">
										<i class="icon-ok"></i> <span>approve</span>
									</a>
								</li>
								<li>
									<a class="disapprove"  href="i<?php echo $url . 'disapprove'?>">
										<i class="icon-remove"></i> <span>disapprove</span>
									</a>
								</li>
							</ul>
						</div>
					</td>
				</tr>
			<?php
}
?>
		</tbody>
	</table>
</div>
