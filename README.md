# axo
CLI tools for working with the Axosoft API

## Installation
```bash
git clone https://github.com/drrcknlsn/axo.git
```

## Configuration
Copy over the example environment file:
```bash
cp .env.example .env
```

Set the appropriate values:
```bash
AXO_BASE_URL=https://youraccount.axosoft.com
AXO_CLIENT_ID=your-client-id-goes-here
AXO_CLIENT_SECRET=yourclientsecret
AXO_USERNAME=yourusername
AXO_PASSWORD=yourpassword
```

You can find your client ID/secret within Axosoft ([instructions](http://developer.axosoft.com/getting-started/private-applications.html)).

## Usage
```bash
./bin/axo <command> [options] [arguments]
```

### Available Commands
```bash
./bin/axo list
```
