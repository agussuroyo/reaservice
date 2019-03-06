<?php

class Rea extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->helper('cookie');
    }

    public function scrape()
    {

        $this->load->helper('url');

        $data = [];
        $data['siteurl'] = site_url();
        $data['baseurl'] = base_url();
        $data['keywords'] = isset($_COOKIE['scrape_keywords']) ? $_COOKIE['scrape_keywords'] : '';
        $data['page'] = isset($_COOKIE['scrape_page']) ? $_COOKIE['scrape_page'] : '';
        $data['channel'] = isset($_COOKIE['channel']) ? $_COOKIE['channel'] : '';
        $this->load->view('scrape', $data);
    }

    public function hit()
    {
        $status = true;

        $keywords = isset($_GET['keywords']) ? $_GET['keywords'] : '';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $channel = isset($_GET['channel']) ? $_GET['channel'] : 'sold';
        $per_page = 100;

        $time = time() + 60 * 60 * 60;

        set_cookie('scrape_keywords', $keywords, $time);
        set_cookie('scrape_page', $page, $time);
        set_cookie('channel', $channel, $time);

        $keys = [];
        if (strpos($keywords, ',') != false) {
            $split = explode(',', $keywords);
            foreach ($split as $word) {
                $keys[] = trim($word);
            }
        } else {
            $keys[] = $keywords;
        }

        $query = [
            'channel' => $channel,
            'page' => $page,
            'pageSize' => $per_page,
            'localities' => array(),
            'filters' => array(
                'surroundingSuburbs' => '',
                'excludeNoSalePrice' => '',
                'ex-under-contract' => '',
                'furnished' => '',
                'keywords' => array(
                    'terms' => $keys
                ),
                'petsAllowed' => '',
                'smokingPermitted' => ''
            )
        ];

        $data = $this->get_results(array(
            'query' => json_encode($query)
        ));

        $total = isset($data[0]['count']) ? $data[0]['count'] : 0;
        $results = isset($data[0]['results']) ? $data[0]['results'] : [];

        foreach ($results as $item) {

            $row = array(
                'id' => $item['listingId'],
                'keyword' => $keywords,
                'channel' => $item['channel'],
                'description' => $item['description'],
                'streetAddress' => isset($item['address']['streetAddress']) ? $item['address']['streetAddress'] : '',
                'postcode' => isset($item['address']['postcode']) ? $item['address']['postcode'] : '',
                'locality' => isset($item['address']['locality']) ? $item['address']['locality'] : '',
                'suburb' => isset($item['address']['suburb']) ? $item['address']['suburb'] : '',
                'state' => isset($item['address']['state']) ? $item['address']['state'] : '',
                'latitude' => isset($item['address']['location']['latitude']) ? $item['address']['location']['latitude'] : NULL,
                'longitude' => isset($item['address']['location']['longitude']) ? $item['address']['location']['longitude'] : NULL,
                'link' => isset($item['_links']['prettyUrl']['href']) ? $item['_links']['prettyUrl']['href'] : '',
                'agencyId' => isset($item['agency']['agencyId']) ? $item['agency']['agencyId'] : '',
                'agencyName' => isset($item['agency']['name']) ? $item['agency']['name'] : '',
                'agencyAddress' => isset($item['agency']['address']) && is_array($item['agency']['address']) ? implode(', ', $item['agency']['address']) : '',
                'agencyWebsite' => isset($item['agency']['website']) ? $item['agency']['website'] : '',
                'agencyPhoneNumber' => isset($item['agency']['phoneNumber']) ? $item['agency']['phoneNumber'] : '',
                'agencyEmail' => isset($item['agency']['email']) ? $item['agency']['email'] : '',
            );

            $row['fullAddress'] = $row['streetAddress'] . ' ' . $row['suburb'] . ', ' . $row['state'] . ' ' . $row['postcode'];

            if (!empty($item['listers'])) {
                for ($i = 0; $i <= 1; $i++) {
                    $lister = isset($item['listers'][$i]) ? $item['listers'][$i] : [];
                    if (!empty($lister)) {
                        $num = $i + 1;
                        $row["agentid{$num}"] = isset($lister['id']) ? $lister['id'] : 0;
                        $row["agentName{$num}"] = isset($lister['name']) ? $lister['name'] : '';
                        $row["agentPhoneNumber{$num}"] = isset($lister['phoneNumber']) ? $lister['phoneNumber'] : '';
                        $row["agentEmail{$num}"] = isset($lister['email']) ? $lister['email'] : '';
                        $row["agentWebsite{$num}"] = isset($lister['website']) ? $lister['website'] : '';
                        $row["agentJobTitle{$num}"] = isset($lister['jobTitle']) ? $lister['jobTitle'] : '';
                    }
                }
            }

            $this->save($row);
        }

        $total_page = ceil($total / $per_page);
        $next_page = $page + 1;
        $do = $total_page >= $next_page;

        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(
                                array(
                                    'status' => $status,
                                    'data' => $data,
                                    'do' => $do,
                                    'next_page' => $next_page,
                                    'query' => $query,
                                    'total_page' => $total_page
                                )
        ));
    }

    public function get($args = [])
    {
        $base = 'https://services.realestate.com.au/services/listings/search';

        $pieces = [];
        foreach ($args as $key => $value) {
            $pieces[] = $key . '=' . urlencode($value);
        }

        $qs = implode('&', $pieces);
        $ch = curl_init("{$base}?{$qs}");

        $ua = array(
            'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/44.0.2403.155 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux i686; rv:64.0) Gecko/20100101 Firefox/64.0',
            'Mozilla/5.0 (X11; Linux i586; rv:63.0) Gecko/20100101 Firefox/63.0',
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.10; rv:62.0) Gecko/20100101 Firefox/62.0',
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:63.0) Gecko/20100101 Firefox/63.0'
        );

        $ip = $this->rand_ip();

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => true,
            CURLOPT_USERAGENT => rand(0, 7),
            CURLOPT_HTTPHEADER => array(
                'REMOTE_ADDR: ' . $ip,
                'X_FORWARDED_FOR: ' . $ip
            )
        ));

        $data = curl_exec($ch);

        curl_close($ch);

        return json_decode($data, TRUE);
    }

    public function save($args = [])
    {
        $this->db->from('properties');
        $this->db->where('id', $args['id']);
        $this->db->limit(1);
        $total = $this->db->count_all_results();


        foreach ($args as $field => $value) {
            $this->db->set($field, $value);
        }

        if ($total > 0) {
            $this->db->set('updated_at', gmdate('Y-m-d H:i:s'));
            $this->db->where('id', $args['id']);
            $this->db->update('properties');
        } else {
            $this->db->set('created_at', gmdate('Y-m-d H:i:s'));
            $this->db->insert('properties');
        }
    }

    public function get_results($args = [])
    {
        $results = [];

        $data = $this->get($args);

        if (isset($data['tieredResults'])) {
            $results = $data['tieredResults'];
            $results[0]['count'] = $data['totalResultsCount'];
        }

        return $results;
    }

    public function rand_ip()
    {
        return mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);
    }

}
