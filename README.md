# itkdev/oidc-server-mock-idp

Build `itkdev/oidc-server-mock-idp` docker image:

```shell
./build
```

## Usage

Include the `itkdev/oidc-server-mock-idp` image and define
[`IDENTITY_RESOURCES_INLINE`](https://github.com/Soluto/oidc-server-mock#simple-configuration)
and
[`USERS_CONFIGURATION_INLINE`](https://github.com/Soluto/oidc-server-mock#simple-configuration)
(or `USERS_CONFIGURATION_PATH`).

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

      # Users can also be defined using USERS_CONFIGURATION_PATH, e.g.
      # USERS_CONFIGURATION_PATH: /tmp/config/.docker/idp-users.yaml
      #
      # Note that you have to add a volume if using USERS_CONFIGURATION_PATH, e.g.
    #volumes:
    #  - .:/tmp/config:ro
```
