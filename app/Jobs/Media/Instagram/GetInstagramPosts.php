<?php

namespace App\Jobs\Media\Instagram;

use App\Models\Media\Instagram\InstagramPost;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use InstagramAPI\Exception\InstagramException;
use InstagramAPI\Response\Model\Item;
use PhpParser\Builder;

class GetInstagramPosts implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * @var bool
	 */
	private $debug;

	/**
	 * @var bool
	 */
	private $cache;

	/**
	 * @var
	 */
	private $client;

	/**
	 * @var
	 */
	private $instagramPosts;

	/**
	 * @var int
	 */
	protected $countPosts = 3;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(bool $debug = false, bool $cache = false) {
		$this->debug = $debug;
		$this->cache = $cache;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(InstagramPost $instagramPosts) {
		$this->instagramPosts = $instagramPosts;
		$getInstagramPosts = $this->getPosts();
		$logMessage = '';
		if (null !== $getInstagramPosts) {
			$getInstagramPosts = array_reverse($getInstagramPosts, true);

			$logMessage .= Carbon::now()->toDayDateTimeString() . ' Get instagram posts: ';

			foreach ($getInstagramPosts as $post) {
				/**
				 * @var Item $post
				 */
				$payload = [];
				$postId = $post->getId();
				$postUrl = $post->getItemUrl();
				$postedAt = $post->getDeviceTimestamp();

				if (0 !== count($this->instagramPosts::where('post_id', $postId)->get()))
					continue;

				$payload['media'] = [];

				if ($post->isCarouselMedia()) {
					foreach ($post->getCarouselMedia() as $media) {
						if ($media->isVideoVersions()) {
							$video = [
									'first_frame' => $media->getImageVersions2()->getCandidates()[0]->getUrl(),
									'url' => $media->getVideoVersions()[0]->getUrl(),
									'isImage' => false,
									'isVideo' => true
							];
							$payload['media'][] = $video;
						} else {
							$image = [
									'url' => $media->getImageVersions2()->getCandidates()[0]->getUrl(),
									'isImage' => true,
									'isVideo' => false
							];
							$payload['media'][] = $image;
						}
					}
				} elseif ($post->isVideoVersions()) {
					$video = [
							'first_frame' => $post->getImageVersions2()->getCandidates()[0]->getUrl(),
							'url' => $post->getVideoVersions()[0]->getUrl(),
							'isImage' => false,
							'isVideo' => true
					];
					$payload['media'][] = $video;
				} else {
					$image = [
							'url' => $post->getImageVersions2()->getCandidates()[0]->getUrl(),
							'isImage' => true,
							'isVideo' => false
					];
					$payload['media'][] = $image;
				}

				if ($post->isCaption()) {
					$payload['caption'] = $post->getCaption()->getText();
				} else {
					$payload['caption'] = '';
				}

				$payload['posted_at'] = $postedAt;
				$logMessage .= $postUrl . ' ';
				$frd = ['post_id' => $postId, 'link' => $postUrl, 'payload' => $payload];
				$this->instagramPosts->create($frd);
			}
		}

		dump($logMessage);
	}

	/**
	 * @return \InstagramAPI\Instagram|null
	 */
	public function getClient() {
		if (null === $this->client) {
			$username = env("INSTAGRAM_LOGIN");
			$password = env("INSTAGRAM_PASSWORD");
			$debug = false;
			$truncatedDebug = false;

			$this->client = new \InstagramAPI\Instagram($debug, $truncatedDebug);

			try {
				$this->client->login($username, $password);
			} catch (\InstagramAPI\Exception\InstagramException $exception) {
				Log::critical('method getClient failed', ['message' => $exception->getMessage(), 'line' => $exception->getLine(), 'code' => $exception->getCode()]);
			}
		}
		return $this->client ?? null;
	}

	/**
	 * @return array|null
	 */
	public function getPosts() {
		try {
			$client = $this->getClient();
			if (null !== $client) {
				$userId = $client->people->getUserIdForName('rustamyan_team');
				$maxId = null;
				$response = $client->timeline->getUserFeed($userId, $maxId);
				$items = $response->getItems();
			}
		} catch (InstagramException $exception) {
			Log::critical('method getPost failed', ['message' => $exception->getMessage(), 'line' => $exception->getLine(), 'code' => $exception->getCode()]);
		}

		return $items ?? null;
	}

	/**
	 * @return int
	 */
	public function getCountPosts(): int {
		return $this->countPosts;
	}
}
