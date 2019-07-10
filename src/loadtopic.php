<?php

$row_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

$row_limit = 10;

if ( isset($_GET['showUserid']) && in_array('IS_ADMIN',$user_rights) ) {
    $userid = (int)$_GET['showUserid'];
}

$sql = "(SELECT 
	elo_topic.topic_id, 
	elo_topic.topic_title, 
	elo_reply.reply_id, 
	elo_reply.reply_date, 
	elo_reply.reply_text, 
	elo_user.user_name as username, 
	elo_user.user_picture,
	(select count(r.reply_id) from elo_reply r where r.topic_id=elo_topic.topic_id) as no, 
	(select r.reply_date from elo_reply r where r.topic_id=elo_topic.topic_id order by r.reply_id asc limit 1) as last_reply_date
FROM ((elo_user INNER JOIN (elo_topic INNER JOIN elo_reply ON elo_topic.topic_id = elo_reply.topic_id) ON elo_user.user_id = elo_reply.user_id) INNER JOIN elo_topic_group ON elo_topic.topic_id = elo_topic_group.topic_id) INNER JOIN elo_group_user ON elo_topic_group.group_id = elo_group_user.group_id
WHERE (((elo_group_user.user_id)=:userid)) group by elo_topic.topic_id order by elo_reply.reply_date
) UNION (SELECT 
	elo_topic.topic_id, 
	elo_topic.topic_title, 
	elo_reply.reply_id, 
	elo_reply.reply_date, 
	elo_reply.reply_text, 
	elo_user.user_name as username, 
	elo_user.user_picture ,
	(select count(r.reply_id) from elo_reply r where r.topic_id=elo_topic.topic_id) as no, 
	(select r.reply_date from elo_reply r where r.topic_id=elo_topic.topic_id order by r.reply_id asc limit 1) as last_reply_date
	FROM elo_user INNER JOIN ((elo_topic INNER JOIN elo_topic_user ON elo_topic.topic_id = elo_topic_user.topic_id) INNER JOIN elo_reply ON elo_topic.topic_id = elo_reply.topic_id) ON elo_user.user_id = elo_reply.user_id WHERE (((elo_topic_user.user_id)=:userid)) group by elo_topic.topic_id order by elo_reply.reply_date ) order by reply_date DESC limit :row_start, :row_limit";

	$statement = $pdo->prepare($sql);
	$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
	$statement->bindValue(':row_start', $row_start, PDO::PARAM_INT);
	$statement->bindValue(':row_limit', $row_limit, PDO::PARAM_INT);
	$statement->execute();

	$topics = array();
	while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
		$topics[] = array(	'topic_title' => $res['topic_title'],
							'no_replies' => intval($res['no'])-1,
							'reply_date' => $res['reply_date'],
							'reply_text' => $res['reply_text'],
							'user_picture' => $res['user_picture'],
							'username' => $res['username'],
							'last_reply_date' => $res['last_reply_date'],
							'topic_id' => $res['topic_id'],
							'href' => $conf['url']."topic.php?id=".$res['topic_id']
						);
	}
	
	$twig_data['topics'] = $topics;
