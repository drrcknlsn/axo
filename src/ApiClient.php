<?php

namespace Drrcknlsn\Axo;

use Exception;
use GuzzleHttp\Client as HttpClient;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class ApiClient
{
    /**
     * @var string
     */
    private const DEFAULT_API_VERSION = 'v6';

    /**
     * @var int
     */
    private const DEFAULT_PAGE_SIZE = 20;

    /**
     * @var string
     */
    private $accessTokenUrl;

    /**
     * @var string
     */
    private $apiVersion;

    /**
     * @var string
     */
    private $authorizeUrl;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var FilesystemAdapter
     */
    private $cache;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(string $apiVersion = self::DEFAULT_API_VERSION)
    {
        $this->apiVersion = $apiVersion;
        $this->httpClient = new HttpClient();

        $this->baseUrl = getenv('AXO_BASE_URL');
        $this->authorizeUrl = $this->baseUrl . '/auth';
        $this->accessTokenUrl = $this->baseUrl . '/api/oauth2/token';

        // TODO(derrick): Inject a cache instead - PSR-6 vs. PSR-16?
        $this->cache = new FilesystemAdapter('', 0, __DIR__ . '/../cache');
    }

    public function getActivity(): array
    {
        $res = $this->getJson('/activity', [
            'page' => 0,
            'page_size' => 10,
        ]);

        return $res;
    }

    public function getAttachment(int $id): array
    {
        $resData = $this->getJson('/attachments/' . $id);

        return $resData['data'];
    }

    /**
     * @param resource $sink
     */
    public function getAttachmentData(int $id, $sink): ResponseInterface
    {
        $path = '/attachments/' . $id . '/data';

        return $this->doRequest('GET', $path, [], [
            'sink' => $sink,
        ]);
    }

    public function getAuditTrail(int $id): array
    {
        $cacheKey = $this->getCacheKey('audit-trail-' . $id);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/audit_trails/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/defects.html#!/defects/_item_type_number_GET_get
     */
    public function getBug(int $id): array
    {
        $cacheKey = $this->getCacheKey('bug-' . $id);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/defects/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/defects.html#!/defects/_item_type_id_attachments_GET_get
     */
    public function getBugAttachments(int $id): array
    {
        $cacheKey = $this->getCacheKey('bug-' . $id . '-attachments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/defects/' . $id . '/attachments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/defects.html#!/defects/_item_type_id_comments_GET_get
     */
    public function getBugComments(int $id): array
    {
        $cacheKey = $this->getCacheKey('defect-' . $id . '-comments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/defects/' . $id . '/comments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/fields.html#!/fields/_fields_custom_GET_get
     */
    public function getBugCustomFields(): array
    {
        return $this->getCustomFields('defects');
    }

    public function getBugHistory(int $id): array
    {
        $cacheKey = $this->getCacheKey('defect-' . $id . '-history');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/defects/' . $id . '/history');

            return $resData['data'];
        });
    }

    public function getBugWorkLogs(int $id, array $options = []): array
    {
        $cacheKey = $this->getCacheKey('feature-' . $id . '-work-logs');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use (
            $id,
            $options
        ) {
            $item->expiresAfter(300);

            $resData = $this->getJson(
                '/defects/' . $id . '/work_logs',
                $options,
            );

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/defects.html#!/defects/_item_type_GET_get
     */
    public function getBugs(array $options = []): array
    {
        $defaults = [
            'page_size' => self::DEFAULT_PAGE_SIZE,
        ];

        return $this->getJson('/defects', array_merge($defaults, $options));
    }

    /**
     * @see http://developer.axosoft.com/api/contacts.html#!/contacts/_contacts_id_GET_get
     */
    public function getContact(int $id): array
    {
        $cacheKey = $this->getCacheKey('contact-' . $id);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/contacts/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/contacts.html#!/contacts/_contacts_GET_get
     */
    public function getContacts(): array
    {
        $resData = $this->getJson('/contacts');

        return $resData['data'];
    }

    /**
     * @see http://developer.axosoft.com/api/incidents.html#!/incidents/_item_type_number_GET_get
     */
    public function getIncident(int $id): array
    {
        $cacheKey = $this->getCacheKey('incident-' . $id);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/incidents/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/incidents.html#!/incidents/_item_type_id_attachments_GET_get
     */
    public function getIncidentAttachments(int $id): array
    {
        $cacheKey = $this->getCacheKey('incident-' . $id . '-attachments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/incidents/' . $id . '/attachments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/incidents.html#!/incidents/_item_type_id_comments_GET_get
     */
    public function getIncidentComments(int $id): array
    {
        $cacheKey = $this->getCacheKey('incident-' . $id . '-comments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/incidents/' . $id . '/comments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/fields.html#!/fields/_fields_custom_GET_get
     */
    public function getIncidentCustomFields(): array
    {
        return $this->getCustomFields('incidents');
    }

    public function getIncidentHistory(int $id): array
    {
        $cacheKey = $this->getCacheKey('incident-' . $id . '-history');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/incidents/' . $id . '/history');

            return $resData['data'];
        });
    }

    public function getIncidentWorkLogs(int $id, array $options = []): array
    {
        $cacheKey = $this->getCacheKey('incident-' . $id . '-work-logs');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use (
            $id,
            $options
        ) {
            $item->expiresAfter(300);

            $resData = $this->getJson(
                '/incidents/' . $id . '/work_logs',
                $options,
            );

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/tasks.html#!/tasks/_item_type_number_GET_get
     */
    public function getTask(int $id): array
    {
        $cacheKey = $this->getCacheKey('task-' . $id);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/tasks/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/tasks.html#!/tasks/_item_type_id_attachments_GET_get
     */
    public function getTaskAttachments(int $id): array
    {
        $cacheKey = $this->getCacheKey('task-' . $id . '-attachments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/tasks/' . $id . '/attachments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/tasks.html#!/tasks/_item_type_id_comments_GET_get
     */
    public function getTaskComments(int $id): array
    {
        $cacheKey = $this->getCacheKey('task-' . $id . '-comments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/tasks/' . $id . '/comments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/fields.html#!/fields/_fields_custom_GET_get
     */
    public function getTaskCustomFields(): array
    {
        return $this->getCustomFields('tasks');
    }

    public function getTaskHistory(int $id): array
    {
        $cacheKey = $this->getCacheKey('task-' . $id . '-history');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/tasks/' . $id . '/history');

            return $resData['data'];
        });
    }

    public function getTaskWorkLogs(int $id, array $options = []): array
    {
        $cacheKey = $this->getCacheKey('task-' . $id . '-work-logs');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use (
            $id,
            $options
        ) {
            $item->expiresAfter(300);

            $resData = $this->getJson(
                '/tasks/' . $id . '/work_logs',
                $options,
            );

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/tasks.html#!/tasks/_item_type_GET_get
     */
    public function getTasks(array $options = []): array
    {
        $defaults = [
            'page_size' => self::DEFAULT_PAGE_SIZE,
        ];

        return $this->getJson('/tasks', array_merge($defaults, $options));
    }

    /**
     * @see http://developer.axosoft.com/api/features.html#!/features/_item_type_GET_get
     */
    public function getFeatures(array $options = []): array
    {
        $defaults = [
            'page_size' => self::DEFAULT_PAGE_SIZE,
        ];

        return $this->getJson('/features', array_merge($defaults, $options));
    }

    /**
     * @see http://developer.axosoft.com/api/features.html#!/features/_item_type_number_GET_get
     */
    public function getFeature(int $id): array
    {
        $cacheKey = $this->getCacheKey('feature-' . $id);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/features/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/features.html#!/features/_item_type_id_attachments_GET_get
     */
    public function getFeatureAttachments(int $id): array
    {
        $cacheKey = $this->getCacheKey('feature-' . $id . '-attachments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/features/' . $id . '/attachments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/features.html#!/features/_item_type_id_comments_GET_get
     */
    public function getFeatureComments(int $id): array
    {
        $cacheKey = $this->getCacheKey('feature-' . $id . '-comments');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/features/' . $id . '/comments');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/fields.html#!/fields/_fields_custom_GET_get
     */
    public function getFeatureCustomFields(): array
    {
        return $this->getCustomFields('features');
    }

    public function getFeatureHistory(int $id): array
    {
        $cacheKey = $this->getCacheKey('feature-' . $id . '-history');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/features/' . $id . '/history');

            return $resData['data'];
        });
    }

    public function getFeatureWorkLogs(int $id, array $options = []): array
    {
        $cacheKey = $this->getCacheKey('feature-' . $id . '-work-logs');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use (
            $id,
            $options
        ) {
            $item->expiresAfter(300);

            $resData = $this->getJson(
                '/features/' . $id . '/work_logs',
                $options,
            );

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/items.html#!/items/_item_type_GET_get
     */
    public function getItems(array $options = []): array
    {
        $defaults = [
            'page_size' => self::DEFAULT_PAGE_SIZE,
        ];

        return $this->getJson('/items', array_merge($defaults, $options));
    }

    /**
     * @see http://developer.axosoft.com/api/fields.html#!/fields/_fields_custom_GET_get
     */
    public function getCustomFields(string $type): array
    {
        $cacheKey = $this->getCacheKey('fields-' . $type);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($type) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/fields/custom', [
                'type' => $type,
            ]);

            return $resData['data'];
        });
    }

    public function getCustomField(string $type, string $name): array
    {
        $fields = $this->getCustomFields($type);

        foreach ($fields as $field) {
            if ($field['name'] === $name) {
                return $field;
            }
        }

        throw new Exception(sprintf(
            'Field "%s" not found for type "%s"',
            $name,
            $type
        ));
    }

    /**
     * @see http://developer.axosoft.com/api/filters.html#!/filters/_filters_GET_get
     */
    public function getFilters(string $type): array
    {
        $cacheKey = $this->getCacheKey('filters-' . $type);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($type) {
            $item->expiresAfter(300);

            $resData = $this->getJson('/filters', [
                'item_type' => $type,
            ]);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/picklists.html#!/picklists/_picklists_picklist_type_GET_get
     */
    public function getPicklist(string $type): array
    {
        $cacheKey = $this->getCacheKey('picklist-' . $type);

        return $this->cache->get($cacheKey, function () use ($type) {
            $resData = $this->getJson('/picklists/' . $type);

            return $resData['data'];
        });
    }

    public function getPicklistOptions(string $type): array
    {
        $origOptions = $this->getPicklist($type);

        usort($origOptions, fn ($a, $b) => $a['order'] <=> $b['order']);

        $options = [];

        foreach ($origOptions as $origOption) {
            $options[$origOption['id']] = $origOption['name'];
        }

        return $options;
    }

    public function getCustomPicklist(int $id): array
    {
        $customPicklists = $this->getPicklist('custom');

        foreach ($customPicklists as $customPicklist) {
            if ($customPicklist['id'] === $id) {
                return $customPicklist;
            }
        }

        throw new Exception(sprintf(
            'Custom picklist not found: %s',
            $id,
        ));
    }

    public function getCustomPicklistOptions(int $id): array
    {
        $customPicklist = $this->getCustomPicklist($id);
        $origOptions = $customPicklist['values'];

        usort($origOptions, fn ($a, $b) => $a['order'] <=> $b['order']);

        $options = [];

        foreach ($origOptions as $origOption) {
            $options[$origOption['id']] = $origOption['value'];
        }

        return $options;
    }

    public function getPicklistItem(string $type, int $id): array
    {
        $picklist = $this->getPicklist($type);

        foreach ($picklist as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        throw new Exception(sprintf(
            'Item "%s" not found in picklist "%s"',
            $id,
            $type
        ));
    }

    /**
     * @see http://developer.axosoft.com/api/projects.html#!/projects/_projects_GET_get
     */
    public function getProjects(): array
    {
        $resData = $this->getJson('/projects');

        return $resData['data'];
    }

    /**
     * @see http://developer.axosoft.com/api/projects.html#!/projects/_projects_id_GET_get
     */
    public function getProject(int $id): array
    {
        $cacheKey = $this->getCacheKey('project-' . $id);

        return $this->cache->get($cacheKey, function () use ($id) {
            $resData = $this->getJson('/projects/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/releases.html#!/releases/_releases_id_GET_get
     */
    public function getRelease(int $id): array
    {
        $cacheKey = $this->getCacheKey('release-' . $id);

        return $this->cache->get($cacheKey, function () use ($id) {
            $resData = $this->getJson('/releases/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/users.html#!/users/_users_id_GET_get
     */
    public function getUser(int $id): array
    {
        $cacheKey = $this->getCacheKey('user-' . $id);

        return $this->cache->get($cacheKey, function () use ($id) {
            $resData = $this->getJson('/users/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/users.html#!/users/_users_GET_get
     */
    public function getUsers(): array
    {
        $cacheKey = $this->getCacheKey('users');

        return $this->cache->get($cacheKey, function () {
            $resData = $this->getJson('/users');

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/workflow_steps.html#!/workflow_steps/_workflow_steps_id_GET_get
     */
    public function getWorkflowStep(int $id): array
    {
        $cacheKey = $this->getCacheKey('workflow-step-' . $id);

        return $this->cache->get($cacheKey, function () use ($id) {
            $resData = $this->getJson('/workflow_steps/' . $id);

            return $resData['data'];
        });
    }

    /**
     * @see http://developer.axosoft.com/api/work_logs.html#!/work_logs/_work_logs_GET_get
     */
    public function getWorkLogs(array $options = []): array
    {
        $defaults = [
            'page_size' => self::DEFAULT_PAGE_SIZE,
        ];

        return $this->getJson('/work_logs', array_merge($defaults, $options));
    }

    public function getJson(string $path, array $params = []): array
    {
        $response = $this->doRequest('GET', $path, $params);

        $resData = json_decode(
            $response->getBody()->getContents(),
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg(), json_last_error());
        }

        return $resData;
    }

    /**
     * Performs an arbitrary authenticated request.
     */
    private function doRequest(
        string $method,
        string $path,
        array $params = [],
        array $options = []
    ): ResponseInterface {
        $accessToken = $this->getAccessToken();

        $options = array_merge(
            $options,
            [
                'headers' => [
                    'authorization' => 'Bearer ' . $accessToken,
                ],
            ],
        );

        if (
            $method === 'GET'
            && !empty($params)
        ) {
            $options['query'] = $params;
        }

        $url = sprintf(
            '%s/api/%s%s',
            $this->baseUrl,
            $this->apiVersion,
            $path
        );

        if (getenv('AXO_DEBUG') === 'true') {
            printf("Request URL: %s\n", $url);
        }

        return $this->httpClient->request(
            $method,
            $url,
            $options
        );
    }

    private function getAccessToken(): string
    {
        $cacheKey = $this->getCacheKey('access-token');

        return $this->cache->get($cacheKey, function () {
            $provider = new GenericProvider(
                [
                    'clientId' => getenv('AXO_CLIENT_ID'),
                    'clientSecret' => getenv('AXO_CLIENT_SECRET'),
                    'urlAuthorize' => $this->authorizeUrl,
                    'urlAccessToken' => $this->accessTokenUrl,
                    'urlResourceOwnerDetails' => '',
                ],
                [
                    'httpClient' => $this->httpClient,
                ]
            );

            // TODO(derrick): Check for token expiration.
            // TODO(derrick): Implement refresh token usage.
            return $provider->getAccessToken('password', [
                'username' => getenv('AXO_USERNAME'),
                'password' => getenv('AXO_PASSWORD'),
            ]);
        });
    }

    private function getCacheKey(string $type): string
    {
        return sprintf(
            '%s.%s',
            md5(getenv('AXO_BASE_URL') . ':' . getenv('AXO_USERNAME')),
            $type
        );
    }
}
