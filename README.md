# itkdev/oidc-server-mock-idp

Build `itkdev/oidc-server-mock-idp` docker image:

```shell
./build
```

## Usage

```yaml
services:
  idp-admin:
    image: itkdev/oidc-server-mock-idp
    ports:
      - '80'
    environment:
      IDENTITY_RESOURCES_INLINE: |
        # https://auth0.com/docs/get-started/apis/scopes/openid-connect-scopes#standard-claims
        - Name: openid
          ClaimTypes:
            - sub
        - Name: email
          ClaimTypes:
            - email
        - Name: profile
          ClaimTypes:
            # Add your custom claims here
            - name
            - groups

      USERS_CONFIGURATION_INLINE: |
        - SubjectId: administrator
          Username: administrator
          Password: administrator
          Claims:
            # Claims added here must be defined above in IDENTITY_RESOURCES_INLINE
          - Type: name
            Value: Admin Jensen
            ValueType: string
          - Type: email
            Value: administrator@example.com
            ValueType: string
          - Type: groups
            Value: '["GG-Rolle-dplager-admin"]'
            ValueType: json
```
