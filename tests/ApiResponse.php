<?php

declare(strict_types=1);

namespace Tests;

trait ApiResponse
{

    private $singleSubjectRecord = <<<EOF
        {
            "authorizedClerks" : [ ],
            "regon" : "123456785",
            "restorationDate" : "2019-02-21",
            "workingAddress" : "ul/ Prosta 49 00-838 Warszawa",
            "hasVirtualAccounts" : true,
            "statusVat" : "Zwolniony",
            "krs" : "0000636771",
            "restorationBasis" : "Ustawa o podatku od towarów i usług art. 96",
            "accountNumbers" : [ "90249000050247256316596736", "90249000050247256316596736" ],
            "registrationDenialBasis" : "Ustawa o podatku od towarów i usług art. 96",
            "nip" : "1111111111",
            "removalDate" : "2019-02-21",
            "partners" : [ ],
            "name" : "ABC Jan Nowak",
            "registrationLegalDate" : "2018-02-21",
            "removalBasis" : "Ustawa o podatku od towarów i usług Art. 97",
            "pesel" : "22222222222",
            "representatives" : [ {
                "firstName" : "Jan",
                "lastName" : "Nowak",
                "nip" : "1111111111",
                "companyName" : "Nazwa firmy"
            }, {
                "firstName" : "Jan",
                "lastName" : "Nowak",
                "nip" : "1111111111",
                "companyName" : "Nazwa firmy"
            } ],
            "residenceAddress" : "ul/ Chmielna 85/87 00-805 Warszawa",
            "registrationDenialDate" : "2019-02-21"
        }
        EOF;

    private $multiSubjectRawResponse = '{"result":{"subjects":[%subjects%],"requestId":"82k5c-869124d"}}';
    private $singleSubjectRawResponse = '{"result":{"subject":[%subject%],"requestId":"82k5c-869124d"}}';

    private $assignedRawResponse = <<<EOF
        {
            "result" : {
                "accountAssigned" : "%answer%",
                "requestDateTime": "19-11-2019 14:58:49",
                "requestId" : "d2n10-84df1a1"
            }
        }
        EOF;

    private $singleEntryRawResponse = '{"identifier":"%identifier%","subjects":[%subjects%]}';
    private $entriesRawResponse = '{"result":{"entries":[%entries%],"requestId":"82k5c-869124d"}}';

    /**
     * Returns raw fake response with subjects for tests.
     *
     * @param boolean $responseType
     * @param integer $subjectsCount
     * @param integer $entriesCount
     */
    public function prepareSubjectRawResponse($responseType = ApiResponseType::TYPE_SINGLE, $subjectsCount = 1, $entriesCount = 0)
    {
        if ((ApiResponseType::TYPE_SINGLE === $responseType) && ($subjectsCount > 1)) {
            $subjectsCount = 1;
        }

        $subjects = $this->prepareMultipleRecords($subjectsCount, $this->singleSubjectRecord);
        $entries = $this->prepareMultipleRecords($entriesCount, $this->singleEntryRawResponse);

        if (ApiResponseType::TYPE_SINGLE === $responseType) {
            return str_replace('%subject%', $subjects, $this->singleSubjectRawResponse);
        } elseif (ApiResponseType::TYPE_SUBJECTS === $responseType) {
            return str_replace('%subjects%', $subjects, $this->multiSubjectRawResponse);
        } else {
            return str_replace(['%entries%', '%subjects%', '%identifier%'], [$entries, $subjects, 'none'], $this->entriesRawResponse);
        }
    }

    private function prepareMultipleRecords($count, $record)
    {
        $response = '';
        for ($idx = 0; $idx < $count; $idx++) {
            if ($idx) {
                $response .= ",\n";
            }
            $response .= $record;
        }
        return $response;
    }

    /**
     * Returns raw fake response with accountAssigned answer.
     *
     * @param boolean|string $name true means "TAK", false means "NIE" and string value is returend instead "TAK" or "NIE".
     */
    public function prepareAssignedRawResponse($answer)
    {
        if ($answer === true) {
            $answer = 'TAK';
        } else if ($answer === false) {
            $answer = 'NIE';
        }
        return str_replace('%answer%', $answer, $this->assignedRawResponse);
    }
}
