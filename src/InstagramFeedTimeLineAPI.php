<?php namespace Riedayme\InstagramKit;

Class InstagramFeedTimeLineAPI
{

	public $cookie;	

	public function SetCookie($data) 
	{
		$this->cookie = $data;
	}

	public function Process($next_max_id = false)
	{

		$parameters = ''; 
		if($next_max_id == true) { 
			$parameters = '?max_id='.$next_max_id; 
		}

		$url = 'https://i.instagram.com/api/v1/feed/timeline/'.$parameters;

		$access = InstagramHelperAPI::curl($url, 'empty' , false , $this->cookie , InstagramUserAgentAPI::GenerateStatic());

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok' AND $response['items'] != null) {		

			if ($response['more_available']) {
				$next_id = $response['next_max_id'];
			}else{
				$next_id = false;
			}

			return [
				'status' => true,
				'response' => $response,
				'next_id' => $next_id
			];

		}else{

			if ($response['status'] == 'ok') {

				return [
					'status' => false,
					'response' => 'no_post'
				];
			}

			return [
				'status' => false,
				'response' => $access['body']
			];
		}
	}

	public function Extract($response)
	{

		if (!$response['status']) return $response;

		$jsondata = $response['response'];
		$items = $jsondata['items'];

		$extract = array();
		foreach ($items as $post){

			/* skip suggest */
			if (array_key_exists('type', $post)) continue;

			$id = $post['pk'];
			$username = $post['user']['username'];
			$code = $post['code'];
			$url = "https://www.instagram.com/p/{$code}/";			
			$caption = (!empty($post['caption'])) ? $post['caption']['text'] : false;
			$haslike = $post['has_liked'];				

			if ($post['media_type'] == 8) {
				$thumbnail = $post['carousel_media'][0]['image_versions2']['candidates'][0]['url'];			
			}else{
				$thumbnail = $post['image_versions2']['candidates'][0]['url'];			
			}

			if ($post['media_type'] == 8) { 

				$media = array();
				foreach ($post['carousel_media'] as $carousel_post) {

					if ($carousel_post['media_type'] == 2) { 
						$sidecarmedia = $carousel_post['video_versions'][0]['url'];
						$sidecartype = "video";
					}elseif ($carousel_post['media_type'] == 1) {
						$sidecarmedia = $carousel_post['image_versions2']['candidates'][0]['url'];
						$sidecartype = "image";
					}

					$media[] = [
						'media' => $sidecarmedia,
						'type' => $sidecartype
					];
				}

				$type = "carousel";

			}elseif ($post['media_type'] == 2) { 
				$media = $post['video_versions'][0]['url'];
				$type = "video";
			}elseif ($post['media_type'] == 1) {
				$media = $post['image_versions2']['candidates'][0]['url'];
				$type = "image";
			}	

			$extract[] = [
				'id' => $id,
				'username' => $username,
				'code' => $code,
				'url' => $url,
				'type' => $type,
				'thumbnail' => $thumbnail,
				'media' => $media,
				'caption' => $caption,
				'haslike' => $haslike,
			];

		}

		return $extract;
	}

}