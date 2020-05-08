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
BASE_URL=https://youraccount.axosoft.com
CLIENT_ID=your-client-id-goes-here
CLIENT_SECRET=yourclientsecret
USERNAME=yourusername
PASSWORD=yourpassword
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
