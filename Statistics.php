<?php
//penghui@gmail.com

include __DIR__ . '/header.php';

echo "<pre>";

$owner = @$_REQUEST["owner"];
$repo  = @$_REQUEST["repo"];

$startPRNumber = isset($_REQUEST["startPRNumber"]) ? $_REQUEST["startPRNumber"] : 1;
$endPRNumber = isset($_REQUEST["endPRNumber"]) ? $_REQUEST["endPRNumber"] : 999999;

//check params
if (mb_strlen($owner) == 0 || mb_strlen($repo) == 0) {
	exit("parameter owner or repo is empty");
}
if (!(is_numeric($startPRNumber) && is_numeric($endPRNumber) && $startPRNumber <= $endPRNumber && $startPRNumber > 0)) {
	exit("wrong parameter startPRNumber or endPRNumber");
}

//check data dir exists and has write permission
if (!is_dir(__DIR__ . "/data")) {
	exit("data dir does not exist");
}

$files = array();
foreach (scandir(__DIR__ . "/data") as $file) {
	if ('.' === $file) continue;
	if ('..' === $file) continue;

	if (preg_match("/^(".$owner.")_(".$repo.")_(\\d+)\\.json$/i", $file, $m)) {
		$number = $m[3];
		if ($number >= $startPRNumber && $number <= $endPRNumber) {
			$files[$m[3]] = $file;
		}
	}
}

ksort($files, SORT_NUMERIC);

$pullRequestStatistics = array();
$reviewerStatistics = array();
foreach ($files as $file) {
	$pr = json_decode(file_get_contents(__DIR__ . "/data/" . $file));
	$author = $pr->user->login;
	$reviewers = array();
	foreach ($pr->reviews as $review) {
		@$reviewers[$review->user->login]++;
	}

	foreach ($reviewers as $reviewer => $count) {
		if($reviewer != $author)
			$reviewerStatistics[$reviewer]++;
	}

	$reviewerStr = "";
	foreach ($reviewers as $reviewer => $count) {
		$reviewerStr .= $reviewer . "(".$count."), ";
	}
	$pullRequestStatistics[] = [
		"number" => $pr->number,
		"state" => $pr->state,
		"merged" => $pr->merged,
		"title" => $pr->title,
		"author" => $author,
		"reviewerStr" => $reviewerStr
	];
}

?>

<table>
	<thead>
	<tr>
		<td>审核人</td>
		<td>审核数</td>
	</tr>
	</thead>
	<?php
	arsort($reviewerStatistics, SORT_NUMERIC);
	foreach ($reviewerStatistics as $reviewer => $count) {
		echo "<tr><td>".$reviewer."</td><td>".$count."</td></tr>\n";
	}
	?>

</table>


<table>
	<thead>
	<tr>
		<td>编号</td>
		<td>状态</td>
		<td>标题</td>
		<td>提交人</td>
		<td>审核人</td>
	</tr>
	</thead>
	<?php
	usort($pullRequestStatistics, function($s1, $s2){return $s1["number"] < $s2["number"];});
	foreach ($pullRequestStatistics as $stat) {
		echo "<tr>";
		echo "<td><a href='https://github.com/tuhu/Tuhu_iOS/pull/".$stat["number"]."' title='".$stat["state"]."'>".$stat["number"]."</a></td>";
		echo "<td>" . strtoupper(substr($stat["state"], 0, 1)) . ($stat["merged"]?"M":".") . "</td>";
		echo "<td width='50%'>".$stat["title"]."</td>";
		echo "<td>".$stat["author"]."</td>";
		echo "<td>".$stat["reviewerStr"]."</td>";
		echo "</tr>\n";
	}
	?>

</table>







