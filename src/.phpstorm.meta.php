<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Hyqo\HTTP\Response::header(),
        0,
        \Hyqo\HTTP\Header::CONTENT_TYPE,
        \Hyqo\HTTP\Header::LOCATION,
    );
    expectedArguments(\Hyqo\HTTP\Response::contentType(), 0, \Hyqo\HTTP\ContentType::JSON);
//    exitPoint(\Hyqo\HTTP\Response::send());
}
