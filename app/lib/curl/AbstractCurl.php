<?php/** * cURL抽象类 * @author chenmin * */abstract class AbstractCurl {    /**     * cURL资源     *     * @var resource     */    protected $_ch = null;    /**     * URL地址     *     * @var string     */    protected $_url = '';    /**     * 是否启用SSL模式     *     * @var boolean     */    protected $_ssl = false;    /**     * 初始化cURL资源     *     */    protected function __construct() {        $this->_ch = curl_init();    }    /**     * cURL抽象方法，处理POST、GET、PUT(暂不提供)     *     * @param array $para     */    abstract protected function _cUrl($para = array());    /**     * 发送socket连接     *     * @param string $url     * @param array $para array('header', 'location', 'cookieFile', 'data)     * @param boolean $return     *     * @return mix [void|string]     */    private function _socket($url, $para, $return) {        $this->_setUrl($url);        /*         * 强制转换为boolean类型，这里不使用(boolean)与settype         */        if (false === isset($para['header'])) {            $para['header'] = false;        } else {            $para['header'] = true;        }        curl_setopt($this->_ch, CURLOPT_HEADER, $para['header']);        if (true === isset($para['set_head_token'])) {            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array("Access-Token:{$para['set_head_token']}"));            unset($para['set_head_token']);        }        unset($para['header']);        if (isset($para['connect_time_ms'])) {            curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT_MS, $para['connect_time_ms']);            unset($para['connect_time_ms']);        } else if (isset($para['connect_time'])) {            curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $para['connect_time']);            unset($para['connect_time']);        } else {            curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 1);        }        if (isset($para['timeout_ms'])) {            curl_setopt($this->_ch, CURLOPT_TIMEOUT, $para['timeout_ms']);            unset($para['timeout_ms']);        } else if (isset($para['timeout'])) {            curl_setopt($this->_ch, CURLOPT_TIMEOUT, $para['timeout']);            unset($para['timeout']);        } else {            curl_setopt($this->_ch, CURLOPT_TIMEOUT, 5);        }        /*         * 处理302         */        if (false === isset($para['location'])) {            $para['location'] = false;        } else {            $para['location'] = true;        }        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, $para['location']);        unset($para['location']);        if (false !== isset($para['cookieFile'])) {            curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $para['cookieFile'][0]);            curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $para['cookieFile'][0]);        }        unset($para['cookieFile']);        /*         * exec执行结果是否保存到变量中         */        if (true === $return) {            curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);        }        /*         * 是否启用SSL验证         */        if (true === $this->_ssl) {            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, true);        }        /*         * 调用子类处理方法         */        $this->_cUrl($para);        // multi_exec        if (Register::get('curl_multi')) {            Multi::getInstance()->addHandler($this->_ch);            throw new RemoteApiException(10086);        }        $result = curl_exec($this->_ch);        $apiStatus = curl_getinfo($this->_ch);        if (isset($apiStatus['http_code']) && 200 == $apiStatus['http_code']) {            if (true === $return) {                curl_close($this->_ch);                return $result;            }        } elseif (isset($apiStatus['http_code']) && 404 == $apiStatus['http_code']) {            $errmsg = curl_error($this->_ch);            curl_close($this->_ch);            throw new RemoteApiException('1001', $errmsg);        } else {            $errmsg = (isset($apiStatus['http_code'])?'(http_code:'.$apiStatus['http_code'].'),':'').curl_error($this->_ch);            curl_close($this->_ch);            throw new RemoteApiException('1000', $errmsg);        }        curl_close($this->_ch);    }    /**     * 初始化URL     *     * @param string $url     *     * @return boolean [true成功 | false失败]     */    private function _setUrl($url) {        $this->_url = $url;        /*         * 以下代码在PHP > 5.3有效         */        if (false !== strstr($this->_url, 'https://', true)) {            $this->_ssl = true;        }        return curl_setopt($this->_ch, CURLOPT_URL, $this->_url);    }    /*     * ************************公共接口********************** */    /**     * 发起通信请求接口     *     * @param string $url     * @param array $para     * @param boolean $return     *     * @return string     */    final public function socket($url, $para = array(), $return = true) {        if (Register::get('curl_multi_complete')) {            return Multi::getResult();        }        return $this->_socket($url, $para, $return);    }}