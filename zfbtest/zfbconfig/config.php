<?php

$config = array (	

		//应用ID,您的APPID。

		'app_id' => "2018083161247005",



		//商户私钥，您的原始格式RSA私钥

		'merchant_private_key' =>'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQC07q/VyY2eH3jzTaTaPZiTVvO7t4orxWJfGQnAE0UXm+1/ZXTG4TtssyhGaJ/2PTlfYeQ55XbvTY4eoSp+3bRIYDFx8PsPGK9L+HxN5GhnFLxnruwU3A9huW/FASLmf6Smamnfhr5eqerpeHySO8GZm5P3L0JtuNg6ZfSjriz3xmhV0ZDwaBtoMot3aPii/lH7W3o/dXbmEbzu6IEBVWekr8Vq0WGiGB+HablIDKAWbOdgDlFYdjF8Tn+VPo2cMNRQpngXL2MZjeatoIDHPdjbDslmlcsL7rdhVSqEMgtmZgNmOno9p2LaNnAe2b9qNEKv1Axfg3+YC6znAue4mrXRAgMBAAECggEAGNgZjuqRJqA9tHL56vnARKMQ7rrCH0aIPnSqsRQ0TYycrPyab2CoD0H+isR9CovXn/BKLWUD0tI8bJpGworg4XtL7tBfOPBzyaPCNdPiPh1LzZToV+jrt8iFv3BrgWkPi8Za6VWQOGFun0ZrHI1WBSimxa9YmMZj8ojjccJN7Gu33tOGUI8yrlRK2rmZBQQw9iVeYGbvYGa3bLF5d8olpHXAothDXGBxsa0d2noAKUebo1Rxyauzk7LLnZzQaWjB6f2EJh7yhp9PXeSIxyncf/kcoJTXtg2AXlFUBMn91/oTdBvvju2KDDxYGjoH9yN5Q8GMRcVltZtn9W0aof4z/QKBgQDjwajsknzsTtH2Ud3K02U+dsPmR8Fj7O+LZXfryWQLgcZb26sRtZ9LzjOgtR+CUURebuNjavU5boAux9mxHmdlpAC4oS79ltecAib4rpmVKzWrpoSaMBazNFg3A/7mXyCOf1HbEsRJn3v1tfSvGpobAfqJZTlFhGevud+3TJFFHwKBgQDLXo1e+xO+UVzoWvvd/3gicX6OxKjX9pldj+Z3FcJ+kbQ2a/3EvWWQ5Ym+zgtlFI/qdlQeQce2C0q8jNzS+8bHCjMLDnpciIxqyAn4MDwn3q8U/0OtUex8kjaa2s4b9FsfGUc/eUHqRNDxMNIctZMIGIFLd6O1mJn1leiABAI3DwKBgEK2zoGFo1wg9nW9o0cvRv/WECobKLXZiI1/inIhytFoES+FGAYW+nNdElhn1bP5lBpJRwgvI2fQS3HojobITidCtAdhB3+2uK91He9ITaqZPp5qJ3t2zJ9vnMt4uyjGAqZa+yI9zAt40Pm3c9X659szaCzo3q7TSv/5ZWOu5PqnAoGAAbmvoVqn9DlkfGQpNtzv+/rShAuPEyX4bx7FacU5fTFnQf7wjDa/IdeQr5m35wehoO+YDxmnxBecbrUTOocATLf9bt6UkyxlZJKF4yEloYD2I0t3G4VSaEwlQnMQxJPyIfVo8VTqBj7HNwSfA7dWo/7xOd6t+OBujfLToiJmkmkCgYAD8XEU5xSyJJdcwu9oPDdzazpuxqijVFVUv+6kq/xTpvBTjju3dwEJKjG6MllbdHFZjk0DvnA9SkVt/3vPY6zOPGzTrFxD3Ped9IHCMkkw+UVCL5wOHjTGL2HNIeWAqrSkYJXPml8SMfkLm7r3tikz11uJUz62Id5fAmIWPE8YFQ==',


		//异步通知地址

		'notify_url' => "http://jinfu.yiaigo.com/wappay/notify_url.php",
		

		//同步跳转

		'return_url' => "http://jinfu.yiaigo.com/wappay/return_url.php",


		//编码格式

		'charset' => "UTF-8",


		//签名方式

		'sign_type'=>"RSA2",


		//支付宝网关

		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",



		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。

		'alipay_public_key' =>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAn0/3ao08S1fbbsmE3faaCySF+wO+5l2OFW1MHADFSNSmIAbp6EZkJxKFqOYteoxl15RLINB0m54Gm7N654j2pDn04hghFnBdLijGhxG9V1GoMCn7EWovmV09Vv6cxrGiAHe2vHOzV6S1/KMj+mXavPJ3483OKGLftVtoZFhqHBdKXCb7yyBGLLuHmiCno6hfFMjbHUZHVdZSsd+aikZiiD26D+hcc4pvMBIv7e8FeaFZ9zMF0kfDxvg+Kl+a8fj6iq0b7xbIysb+qPw0maHY/ToyvYgcT7Kyz3Gpw4043Dg/8ugCp727+LEpAlxzHSVBZ4yCcRY1YNxsEfYXDOu/+QIDAQAB'
	

);