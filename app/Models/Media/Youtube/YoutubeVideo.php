<?php

namespace App\Models\Media\Youtube;

use Alaouy\Youtube\Facades\Youtube;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * App\Models\Media\Youtube\YoutubeVideo
 *
 * @property int                             $id
 * @property string                          $video_id
 * @property string                          $title
 * @property string                          $channel_id
 * @property \Illuminate\Support\Carbon      $published_at
 * @property int|null                        $is_deleted
 * @property array|null                      $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereIsDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereVideoId($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo published()
 * @property string|null $deleted_at
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Media\Youtube\YoutubeVideo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo posts()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media\Youtube\YoutubeVideo whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Media\Youtube\YoutubeVideo withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Media\Youtube\YoutubeVideo withoutTrashed()
 */
class YoutubeVideo extends Model {
	use SoftDeletes;

	/**
	 * @var array
	 */
	protected $fillable = ['video_id', 'title', 'channel_id', 'payload'];

	/**
	 * @var array
	 */
	protected $casts = ['payload' => 'array'];

	/**
	 * @var string
	 */
	protected $channelId = 'UCyuMv8sIthuCu-ZBdMJhQag';

	/**
	 * @var int
	 */
	protected $countVideos = 3;

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->{'id'};
	}

	/**
	 * @return string
	 */
	public function getVideoId(): string {
		return $this->{'video_id'};
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->{'title'};
	}

	/**
	 * @param string $title
	 */
	public function setTitle(string $title) {
		$this->{'title'} = $title;
	}

	/**
	 * @return string
	 */
	public function getLink(): string {
		return 'https://www.youtube.com/watch?v=' . $this->getVideoId();
	}

	/**
	 * @return array
	 */
	public function scopePosts(Builder $query): Builder {
		return $query->orderByDesc('id');
	}

	/**
	 * @return string
	 */
	public function getChannelId(): string {
		return $this->{'channel_id'};
	}

	/**
	 * @return string
	 */
	public function getPublishedAt(): string {
		/**
		 * @var Carbon $date
		 */
		$date = $this->{'published_at'};
		return $date->toDayDateTimeString();
	}

	/**
	 * @return array|null
	 */
	public function getPayload(): ?array {
		return $this->{'payload'};
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function setPayload($key, $value) {
		$payload = $this->getPayload();
		if (null !== $payload) {
			$payload[$key] = $value;
			$this->{'payload'} = $payload;
		}
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		$payload = $this->getPayload();
		$description = $payload['description'];

		return $description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description) {
		$this->setPayload('description', $description);
	}

	/**
	 * @return bool
	 */
	public function updateFromYoutube(): bool {
		$result = false;

		try {
			$videoId = $this->getVideoId();
			$video = json_decode(json_encode(Youtube::getVideoInfo($videoId)), true);
			$payload = $this->getPayload();
			$oldTitle = $this->getTitle();
			$newTitle = $video['snippet']['title'];
			$oldDescription = $this->getDescription();
			$newDescription = $video['snippet']['description'];

			if ($video) {
				if ($oldTitle !== $newTitle) {
					$this->setTitle($video['snippet']['title']);
				}

				if ($oldDescription !== $newDescription) {
					$this->setDescription($newDescription);
				}
			} else {
				$this->delete();
			}

			$this->save();
			$result = true;
		} catch (\Exception $exception) {
			Log::critical('method updateFromYoutube failed', ['message' => $exception->getMessage(), 'line' => $exception->getLine(), 'code' => $exception->getCode()]);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getMedia(): array {
		$result = [];
		$payload = $this->getPayload();

		$result['media'] = [
				[
						'first_frame' => $payload['thumbnail'],
						'url' => 'https://www.youtube.com/embed/' . $this->getVideoId(),
						'isImage' => false,
						'isVideo' => true
				]
		];
		$result['url'] = $this->getLink();
		$result['caption'] = $this->getDescription();
		$result['title'] = $this->getTitle();

		return $result;
	}
}
