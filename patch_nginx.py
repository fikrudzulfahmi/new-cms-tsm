import subprocess

# We can update /etc/nginx/sites-available/default directly since we own or can patch if we use python with sudo or if default is writable? Let's check permissions of /etc/nginx/sites-available/default
import os
print(os.access('/etc/nginx/sites-available/default', os.W_OK))
