# Sisahygo API Error Handling

Integration layer map transport failures และ API failures เป็น safe exceptions ดังนี้:

- `SisahygoConnectionException`
- `SisahygoAuthenticationException`
- `SisahygoAuthorizationException`
- `SisahygoValidationException`
- `SisahygoRateLimitException`
- `SisahygoNotFoundException`
- `SisahygoServerException`
- `SisahygoUnexpectedResponseException`

Exception context ต้องปลอดภัยสำหรับ logs และ diagnostics ข้อมูลที่ใส่ได้ เช่น status code, endpoint name, correlation ID, Client Account ID, credential ID และ fingerprint ห้ามใส่ `X-Api-Key`, decrypted credentials, full payloads, passwords, full addresses หรือ customer personal data ที่ไม่จำเป็น

User-facing Thai error copy ควรถูก map ภายหลังที่ application boundary