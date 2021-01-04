<?php

namespace api;


class Youtube
{
    protected $key;

    private $api_url = "https://www.googleapis.com/youtube/v3/search";
    private $channelUrl = "https://www.youtube.com/channel";
    private $channels_api_url = "https://youtube.googleapis.com/youtube/v3/channels";

    protected $limit;
    protected $pageToken;
    protected $q;

    protected $fileName;

    protected $data;
    protected $channelsData;

    protected $csvData = [];

    protected $csvHeaders = ['ID', 'URL', 'Title', 'Subscribers'];

    public function init($limit = 20, $pageToken = '', $q = "", $fileName)
    {
        $configs = json_decode(file_get_contents(__DIR__ . "/../configs.json"));
        $keyName = $configs->KEY;

        $this->limit = $limit;
        $this->pageToken = $pageToken;
        $this->fileName = $fileName;
        $this->q = $q;
        $this->key = $keyName;

        return $this;
    }

    // records
    public function action()
    {
        $this->getData();

        return $this;
    }

    public function getData()
    {
        $params = [
            'part' => 'snippet,id',
            'maxResults' => $this->limit,
            'key' => $this->key,
            'order' => 'date',
            'type' => 'channel',
            'q'     => $this->q,
        ];

        if (!empty($this->pageToken)) {
            $params['pageToken'] = $this->pageToken;
        }

        $query = http_build_query($params);

        $url = $this->api_url . '?' . $query;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ]);

        $youtuneData = json_decode(curl_exec($curl));

        curl_close($curl);

        $this->data = $youtuneData;
        $this->pageToken = $this->data->nextPageToken;
        $ids  = [];

        foreach ($this->data->items as $d) {
            $ids[] = $d->snippet->channelId;
        }

        $this->channelsData = $this->getChannelInformation($ids);

        return $this;
    }

    public function getChannelInformation(array $ids)
    {
        $params = [
            'part' => 'snippet,statistics',
            'maxResults' => $this->limit,
            'key' => $this->key,
            // 'id'     => $ids,
        ];

        $query = http_build_query($params);
        $idsQuery = "";
        foreach ($ids as $id) {
            $idsQuery .= "&id=$id";
        }

        $url = $this->channels_api_url . '?' . $query . $idsQuery;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ]);

        $youtubeData = json_decode(curl_exec($curl));

        curl_close($curl);

        return $youtubeData;
    }

    public function convertIntoCSV()
    {
        foreach ($this->data->items as $item) {
            $d = [];
            $id = $item->snippet->channelId;
            $d['id'] = $id;
            $d['url'] = $this->channelUrl . '/' . $id;
            $d['title'] = $item->snippet->title;
            $d['subscribers'] = 'Not Found';

            for ($x = 0; $x < count($this->channelsData->items); $x++) {
                if ($this->channelsData->items[$x]->id == $id) {
                    $d['subscribers'] = isset($this->channelsData->items[$x]->statistics->subscriberCount) ? $this->channelsData->items[$x]->statistics->subscriberCount : 'Hidden';
                }
            }

            $this->csvData[] = $d;
        }
        return $this;
    }

    public function download()
    {
        $out = implode(",", $this->csvHeaders) . PHP_EOL;
        foreach ($this->csvData as $arr) {
            $out .= implode(",", $arr) . PHP_EOL;
        }

        return ['name' => $this->fileName, 'data' => $out, 'nextPage' => $this->pageToken];
    }
}
